<?php

namespace App\FrontEnd\Cms\Controllers;

use App\Http\Controllers\Controller;

use App\FrontEnd\Cms\Models\CmsMenuItem;
use App\FrontEnd\Cms\Models\CmsMedia;
use App\FrontEnd\Cms\Models\CmsPage;
use App\Admin\Models\Service;
use App\FrontEnd\Cms\Services\CmsPublishingService;
use App\FrontEnd\Cms\Support\CmsSectionSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class CmsPageController extends Controller
{
    public function index(Request $request): mixed
    {
        if ($request->wantsJson()) {
            $pages = CmsPage::query()
                ->with('sections:id,cms_page_id,type,content,is_active,sort_order')
                ->select(['id', 'title', 'slug', 'status', 'updated_at', 'published_metadata']);

            return DataTables::of($pages)
                ->addIndexColumn()
                ->editColumn('title', fn (CmsPage $page): string => '<a href="'.e(route('cms.pages.edit', $page)).'" class="text-primary fw-bold">'.e($page->title).'</a>')
                ->editColumn('status', fn (CmsPage $page): string => '<span class="badge badge-light-'.($page->status === 'Published' ? 'success' : 'warning').'">'.e($page->status).'</span>'.($page->hasUnpublishedChanges() && $page->status === 'Published' ? ' <span class="badge badge-light-warning">Draft changes</span>' : ''))
                ->editColumn('updated_at', fn (CmsPage $page): string => $page->updated_at?->format('d M Y') ?? '—')
                ->addColumn('action', fn (CmsPage $page): string => '<a class="btn btn-sm btn-light-primary" href="'.e(route('cms.pages.edit', $page)).'">Edit</a>')
                ->rawColumns(['title', 'status', 'action'])
                ->make(true);
        }

        return view('front-end.cms.pages.index');
    }

    public function create(): View
    {
        return view('front-end.cms.pages.create', ['media' => CmsMedia::query()->where('is_public', true)->latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = CmsPage::query()->create($this->validatedPage($request) + [
            'status' => 'Draft',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        activity()->causedBy($request->user())->performedOn($page)->log('CMS page created');

        return redirect()->route('cms.pages.edit', $page)->with('success', 'Draft page created.');
    }

    public function edit(CmsPage $page): View
    {
        $page->load('sections');
        $newSectionType = request()->query('new_section_type');
        $newSectionSchema = is_string($newSectionType) && isset(CmsSectionSchema::all()[$newSectionType])
            ? CmsSectionSchema::get($newSectionType)
            : null;

        return view('front-end.cms.pages.edit', [
            'page' => $page,
            'sections' => $page->sections,
            'schemas' => CmsSectionSchema::all(),
            'media' => CmsMedia::query()->where('is_public', true)->latest()->get(),
            'canPublish' => request()->user()->isPlatformSuperuser() || request()->user()->hasPermission('cms.publish'),
            'navigationReferences' => CmsMenuItem::query()->where('cms_page_id', $page->id)->where('is_visible', true)->count(),
            'newSectionType' => $newSectionType,
            'newSectionSchema' => $newSectionSchema,
        ]);
    }

    public function update(Request $request, CmsPage $page): RedirectResponse
    {
        $validated = $this->validatedPage($request, $page) + ['updated_by' => $request->user()->id];
        DB::transaction(function () use ($page, $validated, $request): void {
            $lockedPage = CmsPage::query()->lockForUpdate()->findOrFail($page->id);
            $lockedPage->update($validated);
            activity()->causedBy($request->user())->performedOn($lockedPage)->log('CMS page draft updated');
        });

        return back()->with('success', 'Page draft saved. Public content is unchanged until it is published.');
    }

    public function preview(CmsPage $page): View
    {
        $page->load('sections');

        $mediaIds = collect($page->sections)->flatMap(function ($section): array {
            return collect($section->content ?? [])->filter(fn ($value, $key): bool => in_array($key, ['media_id', 'background_media_id'], true) && is_numeric($value))->values()->all();
        })->unique()->all();
        $services = collect();
        if ($page->sections->contains('type', 'service_grid')) {
            $services = Service::query()->where('is_active', true)->orderBy('name')->get()->map(function (Service $service): array {
                return ['service' => $service, 'price' => $service->currentPrice()];
            });
        }

        return view('front-end.cms.pages.preview', [
            'page' => $page,
            'sections' => $page->sections,
            'mediaById' => CmsMedia::query()->whereIn('id', $mediaIds)->get()->keyBy('id'),
            'openGraphMedia' => $page->open_graph_media_id ? CmsMedia::query()->find($page->open_graph_media_id) : null,
            'services' => $services,
        ]);
    }

    public function publish(CmsPage $page, CmsPublishingService $publishing): RedirectResponse
    {
        $publishing->publish($page, request()->user());

        return back()->with('success', 'Page published.');
    }

    public function unpublish(CmsPage $page, CmsPublishingService $publishing): RedirectResponse
    {
        $publishing->unpublish($page, request()->user());

        return back()->with('success', 'Page unpublished.');
    }

    public function destroy(CmsPage $page): RedirectResponse
    {
        $isReferenced = false;
        DB::transaction(function () use ($page, &$isReferenced): void {
            $lockedPage = CmsPage::query()->lockForUpdate()->findOrFail($page->id);
            if (CmsMenuItem::query()->where('cms_page_id', $lockedPage->id)->where('is_visible', true)->lockForUpdate()->exists()) {
                $isReferenced = true;

                return;
            }

            $lockedPage->sections()->delete();
            activity()->causedBy(request()->user())->performedOn($lockedPage)->log('CMS page deleted');
            $lockedPage->delete();
        });
        if ($isReferenced) {
            return back()->withErrors(['page' => 'Remove or hide this page from active navigation before deleting it.']);
        }

        return redirect()->route('cms.pages.index')->with('success', 'Page deleted.');
    }

    private function validatedPage(Request $request, ?CmsPage $page = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:150'],
            'slug' => [
                'required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('cms_pages', 'slug')->ignore($page?->id),
                function (string $attribute, mixed $value, \Closure $fail) use ($page): void {
                    $query = CmsPage::query()->where('published_slug', $value);
                    if ($page) {
                        $query->where('id', '!=', $page->id);
                    }
                    if ($query->exists()) {
                        $fail('This slug is already used by a published page.');
                    }
                },
            ],
            'meta_title' => ['required', 'string', 'max:60'],
            'meta_description' => ['required', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url', 'regex:~^https://~i', 'max:500'],
            'open_graph_media_id' => ['nullable', 'integer', Rule::exists('cms_media', 'id')->where('is_public', true)->where('mime_type', 'like', 'image/%')],
        ]);
    }
}
