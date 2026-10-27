<?php

namespace App\FrontEnd\Cms\Controllers;

use App\Http\Controllers\Controller;

use App\FrontEnd\Cms\Models\CmsMedia;
use App\FrontEnd\Cms\Models\CmsPage;
use App\FrontEnd\Cms\Models\CmsSection;
use App\FrontEnd\Cms\Models\CmsSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Throwable;

class CmsMediaController extends Controller
{
    public function index(): View
    {
        return view('front-end.cms.media.index', ['mediaItems' => CmsMedia::query()->with('uploadedBy:id,name')->latest()->paginate(24)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertCanEdit($request);
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4,webm', 'max:51200'],
            'alt_text' => ['nullable', 'string', 'max:200'],
        ]);

        $file = $request->file('file');
        $mime = (string) $file->getMimeType();
        $isImage = str_starts_with($mime, 'image/');
        if ($isImage && blank($validated['alt_text'] ?? null)) {
            return back()->withErrors(['alt_text' => 'Alternative text is required for images.'])->withInput();
        }
        if ($isImage && $file->getSize() > 10 * 1024 * 1024) {
            return back()->withErrors(['file' => 'Images must be 10 MB or smaller.'])->withInput();
        }

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $temporaryPath = null;
        if ($isImage) {
            $temporaryPath = $this->optimizedImagePath($file->getRealPath(), $mime);
            if ($temporaryPath) {
                $extension = 'webp';
            } else {
                $temporaryPath = $file->getRealPath();
            }
            try {
                ImageOptimizer::optimize($temporaryPath);
            } catch (Throwable) {
                // Keep the converted or validated original when optional optimizer binaries are unavailable.
            }
        } else {
            $temporaryPath = $file->getRealPath();
        }

        $disk = (string) config('cms.media_disk', 'public');
        $path = 'cms/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
        $stream = fopen($temporaryPath, 'rb');
        try {
            $stored = Storage::disk($disk)->put($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if ($temporaryPath && $temporaryPath !== $file->getRealPath() && file_exists($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
        if (! $stored) {
            return back()->withErrors(['file' => 'The file could not be saved. Check the configured CMS media disk.']);
        }

        $storedMime = Storage::disk($disk)->mimeType($path) ?: $mime;
        $media = CmsMedia::query()->create([
            'disk' => $disk,
            'path' => $path,
            'original_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime_type' => $storedMime,
            'size_bytes' => Storage::disk($disk)->size($path),
            'alt_text' => $isImage ? $validated['alt_text'] : null,
            'is_public' => true,
            'uploaded_by' => $request->user()->id,
        ]);
        activity()->causedBy($request->user())->performedOn($media)->log('CMS media uploaded');

        return back()->with('success', 'Media uploaded to the public website library.');
    }

    public function update(Request $request, CmsMedia $media): RedirectResponse
    {
        $this->assertCanEdit($request);
        $validated = $request->validate([
            'alt_text' => [str_starts_with($media->mime_type, 'image/') ? 'required' : 'nullable', 'string', 'max:200'],
            'is_public' => ['required', 'boolean'],
        ]);
        if (! $validated['is_public'] && $this->isReferenced($media->id)) {
            return back()->withErrors(['media' => 'This file is referenced by a draft or published page and cannot be hidden.']);
        }

        $media->update(['alt_text' => $validated['alt_text'] ?? null, 'is_public' => (bool) $validated['is_public']]);
        activity()->causedBy($request->user())->performedOn($media)->log('CMS media metadata updated');
        $this->flushPublicMediaCache();

        return back()->with('success', 'Media details updated.');
    }

    public function destroy(Request $request, CmsMedia $media): RedirectResponse
    {
        $this->assertCanEdit($request);
        if ($this->isReferenced($media->id)) {
            return back()->withErrors(['media' => 'This file is referenced by a draft or published page and cannot be deleted.']);
        }

        Storage::disk($media->disk)->delete($media->path);
        activity()->causedBy($request->user())->performedOn($media)->log('CMS media deleted');
        $media->delete();
        $this->flushPublicMediaCache();

        return back()->with('success', 'Media deleted.');
    }

    private function isReferenced(int $mediaId): bool
    {
        if (CmsPage::query()->where('open_graph_media_id', $mediaId)->exists()) {
            return true;
        }
        $logo = CmsSetting::query()->where('key', 'logo_media_id')->first();
        if ((int) data_get($logo?->value, 'value') === $mediaId) {
            return true;
        }

        foreach (CmsPage::query()->whereNotNull('published_metadata')->get(['published_metadata']) as $page) {
            if ($this->containsMedia($page->published_metadata, $mediaId)) {
                return true;
            }
        }
        foreach (CmsSection::query()->get(['content', 'published_content']) as $section) {
            if ($this->containsMedia($section->content, $mediaId) || $this->containsMedia($section->published_content, $mediaId)) {
                return true;
            }
        }

        return false;
    }

    private function containsMedia(mixed $value, int $mediaId): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $key => $child) {
            if (in_array($key, ['media_id', 'background_media_id', 'open_graph_media_id'], true) && (int) $child === $mediaId) {
                return true;
            }
            if ($this->containsMedia($child, $mediaId)) {
                return true;
            }
        }

        return false;
    }

    private function optimizedImagePath(string $source, string $mime): ?string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring') || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }
        $contents = file_get_contents($source);
        $image = $contents === false ? false : @imagecreatefromstring($contents);
        if (! $image) {
            return null;
        }
        $path = tempnam(sys_get_temp_dir(), 'cms-webp-');
        if ($path === false) {
            imagedestroy($image);

            return null;
        }
        if ($mime === 'image/png') {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
        $success = imagewebp($image, $path, 82);
        imagedestroy($image);
        if (! $success) {
            @unlink($path);

            return null;
        }

        return $path;
    }

    private function assertCanEdit(Request $request): void
    {
        abort_unless($request->user()->isPlatformSuperuser() || $request->user()->hasPermission('cms.edit'), 403);
    }

    private function flushPublicMediaCache(): void
    {
        CmsPage::query()->whereNotNull('published_slug')->pluck('published_slug')->each(fn (string $slug) => Cache::forget('cms.page.'.sha1($slug)));
        Cache::forget('cms.settings.public');
    }
}
