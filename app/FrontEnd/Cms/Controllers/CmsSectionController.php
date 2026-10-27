<?php

namespace App\FrontEnd\Cms\Controllers;

use App\Http\Controllers\Controller;

use App\FrontEnd\Cms\Models\CmsPage;
use App\FrontEnd\Cms\Models\CmsSection;
use App\FrontEnd\Cms\Support\CmsHtmlSanitizer;
use App\FrontEnd\Cms\Support\CmsSectionSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CmsSectionController extends Controller
{
    public function store(Request $request, CmsPage $page): RedirectResponse
    {
        $this->assertCanEdit($request);
        $validated = $this->validatedContent($request);
        $type = $validated['type'];
        $section = DB::transaction(function () use ($page, $type, $validated, $request): CmsSection {
            $lockedPage = CmsPage::query()->lockForUpdate()->findOrFail($page->id);

            return $lockedPage->sections()->create([
                'type' => $type,
                'content' => $this->sanitizeContent($type, $validated['content']),
                'is_active' => $validated['is_active'],
                'sort_order' => (int) $lockedPage->sections()->max('sort_order') + 10,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        });
        activity()->causedBy($request->user())->performedOn($section)->log('CMS section draft created');

        return redirect()->route('cms.pages.edit', $page)->with('success', 'Section added to the draft.');
    }

    public function update(Request $request, CmsPage $page, CmsSection $section): RedirectResponse
    {
        abort_unless($section->cms_page_id === $page->id, 404);
        $this->assertCanEdit($request);
        $validated = $this->validatedContent($request, $section->type);
        DB::transaction(function () use ($page, $section, $validated, $request): void {
            CmsPage::query()->lockForUpdate()->findOrFail($page->id);
            $lockedSection = CmsSection::query()->where('cms_page_id', $page->id)->lockForUpdate()->findOrFail($section->id);
            $lockedSection->update([
                'content' => $this->sanitizeContent($lockedSection->type, $validated['content']),
                'is_active' => $validated['is_active'],
                'updated_by' => $request->user()->id,
            ]);
            activity()->causedBy($request->user())->performedOn($lockedSection)->log('CMS section draft updated');
        });

        return redirect()->route('cms.pages.edit', $page)->with('success', 'Section draft saved.');
    }

    public function destroy(Request $request, CmsPage $page, CmsSection $section): RedirectResponse
    {
        abort_unless($section->cms_page_id === $page->id, 404);
        $this->assertCanEdit($request);
        DB::transaction(function () use ($page, $section, $request): void {
            CmsPage::query()->lockForUpdate()->findOrFail($page->id);
            $lockedSection = CmsSection::query()->where('cms_page_id', $page->id)->lockForUpdate()->findOrFail($section->id);
            activity()->causedBy($request->user())->performedOn($lockedSection)->log('CMS section draft deleted');
            $lockedSection->delete();
        });

        return back()->with('success', 'Section removed from the draft.');
    }

    public function reorder(Request $request, CmsPage $page, CmsSection $section): RedirectResponse
    {
        abort_unless($section->cms_page_id === $page->id, 404);
        $this->assertCanEdit($request);
        if ($request->has('section_ids')) {
            $ids = $request->validate([
                'section_ids' => ['required', 'array', 'min:1'],
                'section_ids.*' => ['required', 'integer', 'distinct', Rule::exists('cms_sections', 'id')->where('cms_page_id', $page->id)],
            ])['section_ids'];

            DB::transaction(function () use ($request, $page, $ids): void {
                CmsPage::query()->lockForUpdate()->findOrFail($page->id);
                $sections = $page->sections()->lockForUpdate()->get();
                if ($sections->pluck('id')->sort()->values()->all() !== collect($ids)->map(fn ($id): int => (int) $id)->sort()->values()->all()) {
                    abort(422, 'The submitted section order does not match this page.');
                }

                foreach ($ids as $index => $id) {
                    $page->sections()->whereKey($id)->update(['sort_order' => ($index + 1) * 10, 'updated_by' => $request->user()->id]);
                }
                activity()->causedBy($request->user())->performedOn($page)->log('CMS page sections reordered');
            });

            return back()->with('success', 'Draft sections reordered.');
        }

        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        DB::transaction(function () use ($request, $page, $section, $direction): void {
            CmsPage::query()->lockForUpdate()->findOrFail($page->id);
            $sections = $page->sections()->orderBy('sort_order')->lockForUpdate()->get()->values();
            $from = $sections->search(fn (CmsSection $item): bool => $item->is($section));
            $to = $direction === 'up' ? $from - 1 : $from + 1;
            if ($from === false || $to < 0 || $to >= $sections->count()) {
                return;
            }

            [$sections[$from], $sections[$to]] = [$sections[$to], $sections[$from]];
            foreach ($sections as $index => $item) {
                $item->update(['sort_order' => ($index + 1) * 10, 'updated_by' => $request->user()->id]);
            }
            activity()->causedBy($request->user())->performedOn($page)->log('CMS page sections reordered');
        });

        return back()->with('success', 'Draft sections reordered.');
    }

    /** @return array<string, mixed> */
    private function validatedContent(Request $request, ?string $forcedType = null): array
    {
        $type = $forcedType ?? $request->input('type');
        if (! $forcedType) {
            $request->validate(['type' => ['required', 'string', Rule::in(array_keys(CmsSectionSchema::all()))]]);
        }
        $schema = CmsSectionSchema::get((string) $type);
        $rules = CmsSectionSchema::rules($schema['fields']);
        $rules['is_active'] = ['required', 'boolean'];
        $validated = $request->validate($rules);

        return ['type' => $type, 'content' => $validated['content'], 'is_active' => (bool) $validated['is_active']];
    }

    /** @param array<string, mixed> $content
     *  @return array<string, mixed>
     */
    private function sanitizeContent(string $type, array $content): array
    {
        foreach (CmsSectionSchema::get($type)['fields'] as $key => $field) {
            if (($field['type'] ?? null) === 'html' && isset($content[$key])) {
                $content[$key] = CmsHtmlSanitizer::sanitize($content[$key]);
            } elseif (($field['type'] ?? null) === 'boolean') {
                $content[$key] = (bool) ($content[$key] ?? false);
            } elseif (($field['type'] ?? null) === 'repeater') {
                foreach ($content[$key] ?? [] as &$item) {
                    foreach ($field['fields'] as $subKey => $subField) {
                        if (($subField['type'] ?? null) === 'boolean') {
                            $item[$subKey] = (bool) ($item[$subKey] ?? false);
                        }
                    }
                }
                unset($item);
            }
        }

        return $content;
    }

    private function assertCanEdit(Request $request): void
    {
        abort_unless($request->user()->isPlatformSuperuser() || $request->user()->hasPermission('cms.edit'), 403);
    }
}
