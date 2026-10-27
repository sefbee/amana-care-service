<?php

namespace App\FrontEnd\Cms\Controllers;

use App\Http\Controllers\Controller;

use App\FrontEnd\Cms\Models\CmsMenuItem;
use App\FrontEnd\Cms\Models\CmsPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CmsMenuController extends Controller
{
    public function index(): View
    {
        return view('front-end.cms.navigation.index', [
            'items' => CmsMenuItem::query()->with(['page', 'children.page'])->whereNull('parent_id')->orderBy('location')->orderBy('sort_order')->get(),
            'pages' => CmsPage::query()->where('status', 'Published')->orderBy('title')->get(['id', 'title', 'published_slug']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertCanPublish($request);
        $validated = $this->validatedItem($request);
        $item = DB::transaction(function () use ($validated): CmsMenuItem {
            $attributes = $this->itemAttributes($validated);
            $attributes['sort_order'] = (int) CmsMenuItem::query()->where('location', $validated['location'])->where('parent_id', $validated['parent_id'] ?? null)->lockForUpdate()->max('sort_order') + 10;

            return CmsMenuItem::query()->create($attributes);
        });
        activity()->causedBy($request->user())->performedOn($item)->log('CMS navigation item created');
        $this->flushNavigationCache();

        return back()->with('success', 'Navigation item added.');
    }

    public function update(Request $request, CmsMenuItem $item): RedirectResponse
    {
        $this->assertCanPublish($request);
        $validated = $this->validatedItem($request, $item);
        if ($item->children()->exists() && ($item->parent_id !== null || $validated['location'] !== $item->location)) {
            return back()->withErrors(['navigation' => 'A parent link with child links must stay top-level in its current location.']);
        }
        DB::transaction(fn () => $item->update($this->itemAttributes($validated)));
        activity()->causedBy($request->user())->performedOn($item)->log('CMS navigation item updated');
        $this->flushNavigationCache();

        return back()->with('success', 'Navigation item updated.');
    }

    public function destroy(Request $request, CmsMenuItem $item): RedirectResponse
    {
        $this->assertCanPublish($request);
        if ($item->children()->exists()) {
            return back()->withErrors(['navigation' => 'Move or remove this item’s child links first.']);
        }

        activity()->causedBy($request->user())->performedOn($item)->log('CMS navigation item deleted');
        $item->delete();
        $this->flushNavigationCache();

        return back()->with('success', 'Navigation item deleted.');
    }

    public function reorder(Request $request, CmsMenuItem $item): RedirectResponse
    {
        $this->assertCanPublish($request);
        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        DB::transaction(function () use ($item, $direction, $request): void {
            $siblings = CmsMenuItem::query()->where('location', $item->location)->where('parent_id', $item->parent_id)->orderBy('sort_order')->lockForUpdate()->get()->values();
            $from = $siblings->search(fn (CmsMenuItem $sibling): bool => $sibling->is($item));
            if ($from === false) {
                return;
            }
            $to = $direction === 'up' ? $from - 1 : $from + 1;
            if ($to < 0 || $to >= $siblings->count()) {
                return;
            }

            [$siblings[$from], $siblings[$to]] = [$siblings[$to], $siblings[$from]];
            foreach ($siblings as $index => $sibling) {
                $sibling->update(['sort_order' => ($index + 1) * 10]);
            }
            activity()->causedBy($request->user())->performedOn($item)->log('CMS navigation reordered');
        });

        $this->flushNavigationCache();

        return back()->with('success', 'Navigation order updated.');
    }

    /** @return array<string, mixed> */
    private function validatedItem(Request $request, ?CmsMenuItem $item = null): array
    {
        $validated = $request->validate([
            'location' => ['required', Rule::in(['header', 'footer'])],
            'parent_id' => ['nullable', 'integer', Rule::exists('cms_menu_items', 'id')],
            'cms_page_id' => ['nullable', 'integer', Rule::exists('cms_pages', 'id')->where('status', 'Published')],
            'label' => ['required', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:500'],
            'is_external' => ['required', 'boolean'],
            'opens_new_tab' => ['required', 'boolean'],
            'is_visible' => ['required', 'boolean'],
        ]);

        if (! empty($validated['parent_id'])) {
            $parent = CmsMenuItem::query()->findOrFail($validated['parent_id']);
            if ($parent->parent_id !== null || $parent->location !== $validated['location'] || ($item && $parent->id === $item->id)) {
                abort(422, 'Navigation supports one level of nesting within the same location.');
            }
        }

        if (! empty($validated['cms_page_id']) && ! empty($validated['is_external'])) {
            abort(422, 'A CMS page link cannot be marked as external.');
        }

        if (empty($validated['cms_page_id'])) {
            $url = (string) ($validated['url'] ?? '');
            if ($validated['is_external']) {
                $urlParts = parse_url($url);
                $safeHttps = filter_var($url, FILTER_VALIDATE_URL) && is_array($urlParts)
                    && strtolower((string) ($urlParts['scheme'] ?? '')) === 'https'
                    && ! empty($urlParts['host']) && ! isset($urlParts['user']) && ! isset($urlParts['pass']);
                $safeEmail = preg_match('/^mailto:[^\\s@]+@[^\\s@]+$/i', $url) === 1;
                $safePhone = preg_match('/^tel:\\+?[0-9().-]{7,20}$/i', $url) === 1;
                if (! $safeHttps && ! $safeEmail && ! $safePhone) {
                    abort(422, 'External links must use HTTPS, mailto, or tel with a valid address.');
                }
            } elseif (! str_starts_with($url, '/') || str_starts_with($url, '//') || str_contains($url, '\\') || preg_match('/[\x00-\x1F]/', $url) || str_contains($url, '..')) {
                abort(422, 'Internal links must be safe paths beginning with a single slash.');
            }
        }

        return $validated;
    }

    /** @param array<string, mixed> $validated
     *  @return array<string, mixed>
     */
    private function itemAttributes(array $validated): array
    {
        $page = ! empty($validated['cms_page_id'])
            ? CmsPage::query()->where('status', 'Published')->lockForUpdate()->findOrFail($validated['cms_page_id'])
            : null;

        return [
            'location' => $validated['location'],
            'parent_id' => $validated['parent_id'] ?? null,
            'cms_page_id' => $page?->id,
            'label' => $validated['label'],
            'url' => $page ? '/'.$page->published_slug : $validated['url'],
            'is_external' => (bool) $validated['is_external'],
            'opens_new_tab' => (bool) $validated['opens_new_tab'],
            'is_visible' => (bool) $validated['is_visible'],
        ];
    }

    private function assertCanPublish(Request $request): void
    {
        abort_unless($request->user()->isPlatformSuperuser() || $request->user()->hasPermission('cms.publish'), 403);
    }

    private function flushNavigationCache(): void
    {
        Cache::forget('cms.navigation.header');
        Cache::forget('cms.navigation.footer');
    }
}
