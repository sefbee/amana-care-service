<?php

namespace App\FrontEnd\Cms\Services;

use App\FrontEnd\Cms\Models\CmsMenuItem;
use App\FrontEnd\Cms\Models\CmsPage;
use App\FrontEnd\Cms\Models\CmsSection;
use App\Admin\Models\User;
use App\FrontEnd\Cms\Support\CmsSectionSchema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CmsPublishingService
{
    public function publish(CmsPage $page, User $actor): void
    {
        abort_unless($actor->isPlatformSuperuser() || $actor->hasPermission('cms.publish'), 403);
        $previousPublishedSlug = $page->published_slug;

        DB::transaction(function () use ($page, $actor): void {
            $page = CmsPage::query()->lockForUpdate()->findOrFail($page->id);
            $existing = CmsPage::query()->where('published_slug', $page->slug)->where('id', '!=', $page->id)->exists();
            if ($existing) {
                throw ValidationException::withMessages(['slug' => 'This slug is already published on another page.']);
            }

            $page->load('sections');
            $this->validatePublicationSnapshot($page);
            foreach ($page->sections as $section) {
                $section->forceFill([
                    'published_content' => $section->content,
                    'published_is_active' => $section->is_active,
                    'published_sort_order' => $section->sort_order,
                ])->save();
            }

            $page->forceFill([
                'published_slug' => $page->slug,
                'published_metadata' => $page->draftSnapshot(),
                'status' => 'Published',
                'published_at' => now(),
                'published_by' => $actor->id,
            ])->save();

            CmsMenuItem::query()->where('cms_page_id', $page->id)->update(['url' => '/'.$page->slug]);
            activity()->causedBy($actor)->performedOn($page)->log('CMS page published');
        });

        $this->flushPublicCache($previousPublishedSlug, $page->published_slug, $page->slug);
    }

    public function unpublish(CmsPage $page, User $actor): void
    {
        abort_unless($actor->isPlatformSuperuser() || $actor->hasPermission('cms.publish'), 403);

        $publishedSlug = $page->published_slug;
        DB::transaction(function () use ($page, $actor): void {
            $lockedPage = CmsPage::query()->lockForUpdate()->findOrFail($page->id);
            if (CmsMenuItem::query()->where('cms_page_id', $lockedPage->id)->where('is_visible', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['page' => 'Remove or hide this page from navigation before unpublishing it.']);
            }

            $lockedPage->update(['status' => 'Draft', 'published_by' => $actor->id]);
            activity()->causedBy($actor)->performedOn($lockedPage)->log('CMS page unpublished');
        });
        $this->flushPublicCache($publishedSlug);
    }

    public function assertCanEdit(User $actor): void
    {
        abort_unless($actor->isPlatformSuperuser() || $actor->hasPermission('cms.edit'), 403);
    }

    private function flushPublicCache(?string ...$slugs): void
    {
        foreach (array_filter($slugs) as $slug) {
            Cache::forget('cms.page.'.sha1($slug));
        }
        Cache::forget('cms.pages.index');
        Cache::forget('cms.navigation.header');
        Cache::forget('cms.navigation.footer');
        Cache::forget('cms.settings.public');
    }

    private function validatePublicationSnapshot(CmsPage $page): void
    {
        foreach ($page->sections->where('is_active', true) as $section) {
            CmsSectionSchema::get($section->type);
            if ($section->type !== 'dynamic_lead_form') {
                continue;
            }

            $fields = collect($section->content['fields'] ?? []);
            $keys = $fields->pluck('key')->filter()->values();
            $fieldMap = $fields->keyBy('key');
            if (! $keys->contains('full_name') || ! $keys->contains('phone') || $keys->duplicates()->isNotEmpty()
                || ! (bool) data_get($fieldMap->get('full_name'), 'required')
                || ! (bool) data_get($fieldMap->get('phone'), 'required')) {
                throw ValidationException::withMessages([
                    'page' => 'A published inquiry form must have unique fields and include full name and phone.',
                ]);
            }
        }
    }
}
