<?php

namespace App\FrontEnd\Cms\Controllers;

use App\Http\Controllers\Controller;

use App\FrontEnd\Cms\Models\CmsMedia;
use App\FrontEnd\Cms\Models\CmsSetting;
use App\FrontEnd\Cms\Support\CmsSettingSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CmsSettingController extends Controller
{
    public function edit(): View
    {
        $records = CmsSetting::query()->get()->keyBy('key');
        $settings = $records->map(fn (CmsSetting $setting) => $setting->value['value'] ?? null);
        $publishedSettings = $records->map(fn (CmsSetting $setting) => $setting->published_value['value'] ?? null);
        $settings->put('turnstile_site_key', $settings->get('turnstile_site_key') ?? config('cms.turnstile_site_key'));
        $hasPendingChanges = collect(array_keys(CmsSettingSchema::fields()))->contains(fn (string $key): bool => $settings->get($key) !== $publishedSettings->get($key));

        return view('front-end.cms.settings.edit', [
            'fields' => CmsSettingSchema::fields(),
            'settings' => $settings,
            'publishedSettings' => $publishedSettings,
            'hasPendingChanges' => $hasPendingChanges,
            'media' => CmsMedia::query()->where('is_public', true)->where('mime_type', 'like', 'image/%')->latest()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isPlatformSuperuser() || $request->user()->hasPermission('cms.edit'), 403);
        $validated = $request->validate(CmsSettingSchema::rules());

        DB::transaction(function () use ($validated, $request): void {
            foreach ($validated['settings'] as $key => $value) {
                $definition = CmsSettingSchema::fields()[$key];
                $setting = CmsSetting::query()->firstOrNew(['key' => $key]);
                $setting->value = ['value' => $value];
                $setting->is_public = $definition['public'];
                $setting->updated_by = $request->user()->id;
                $setting->save();

                activity()->causedBy($request->user())->performedOn($setting)
                    ->withProperties(['key' => $key, 'draft_value' => $setting->value])
                    ->log('CMS website setting updated');
            }
        });

        return back()->with('success', 'Website settings saved as a draft. They are not public until an Operations Manager publishes them.');
    }

    public function publish(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isPlatformSuperuser() || $request->user()->hasPermission('cms.publish'), 403);
        $definitions = CmsSettingSchema::fields();

        DB::transaction(function () use ($request, $definitions): void {
            foreach (CmsSetting::query()->whereIn('key', array_keys($definitions))->lockForUpdate()->get() as $setting) {
                $setting->published_value = $setting->value;
                $setting->save();
                activity()->causedBy($request->user())->performedOn($setting)
                    ->withProperties(['key' => $setting->key, 'published_value' => $setting->published_value])
                    ->log('CMS website setting published');
            }
        });
        Cache::forget('cms.settings.public');

        return back()->with('success', 'Website settings published.');
    }
}
