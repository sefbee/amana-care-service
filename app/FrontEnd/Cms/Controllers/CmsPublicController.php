<?php

namespace App\FrontEnd\Cms\Controllers;

use App\Http\Controllers\Controller;
use App\FrontEnd\Cms\Models\CmsMenuItem;
use App\FrontEnd\Cms\Models\CmsMedia;
use App\FrontEnd\Cms\Models\CmsPage;
use App\FrontEnd\Cms\Models\CmsSetting;
use App\Admin\Models\Lead;
use App\Admin\Models\LeadConsentRecord;
use App\Admin\Models\Service;
use App\Admin\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CmsPublicController extends Controller
{
    public function pages(): JsonResponse
    {
        $pages = Cache::remember('cms.pages.index', now()->addMinutes(5), fn (): array => CmsPage::query()
            ->where('status', 'Published')
            ->whereNotNull('published_slug')
            ->orderBy('published_slug')
            ->get(['published_slug', 'published_metadata', 'published_at'])
            ->map(fn (CmsPage $page): array => [
                'title' => data_get($page->published_metadata, 'title'),
                'slug' => $page->published_slug,
                'seo' => [
                    'title' => data_get($page->published_metadata, 'meta_title'),
                    'description' => data_get($page->published_metadata, 'meta_description'),
                    'canonical_url' => data_get($page->published_metadata, 'canonical_url'),
                ],
                'published_at' => $page->published_at?->toISOString(),
            ])->values()->all());

        return response()->json(['data' => $pages])->header('Cache-Control', 'public, max-age=60, s-maxage=300');
    }

    public function page(string $slug): JsonResponse
    {
        $page = CmsPage::query()->where('status', 'Published')->where('published_slug', $slug)->first();
        abort_unless($page && is_array($page->published_metadata), 404);

        $snapshot = $page->published_metadata;
        $sections = collect($snapshot['sections'] ?? [])
            ->filter(fn (array $section): bool => (bool) ($section['is_active'] ?? false))
            ->sortBy('sort_order')
            ->values()
            ->map(function (array $section): array {
                $content = $this->resolveMediaReferences($section['content'] ?? []);
                if (($section['type'] ?? null) === 'service_grid') {
                    $content['services'] = $this->serviceGrid($content);
                }

                return [
                    'id' => $section['id'],
                    'type' => $section['type'],
                    'content' => $content,
                    'order' => $section['sort_order'],
                ];
            });

        return response()->json([
            'data' => [
                'title' => $snapshot['title'],
                'slug' => $snapshot['slug'],
                'seo' => [
                    'title' => $snapshot['meta_title'],
                    'description' => $snapshot['meta_description'],
                    'canonical_url' => $snapshot['canonical_url'] ?? null,
                    'open_graph_image' => $this->mediaObject($snapshot['open_graph_media_id'] ?? null),
                ],
                'sections' => $sections,
                'published_at' => $page->published_at?->toISOString(),
            ],
        ])->header('Cache-Control', 'public, max-age=0, s-maxage=0, must-revalidate');
    }

    public function navigation(string $location): JsonResponse
    {
        abort_unless(in_array($location, ['header', 'footer'], true), 404);
        $links = Cache::remember('cms.navigation.'.$location, now()->addMinutes(10), function () use ($location): array {
            return CmsMenuItem::query()
                ->where('location', $location)
                ->whereNull('parent_id')
                ->where('is_visible', true)
                ->with(['page', 'children' => fn ($query) => $query->where('is_visible', true)->orderBy('sort_order')->with('page')])
                ->orderBy('sort_order')
                ->get()
                ->filter(fn (CmsMenuItem $item): bool => $this->menuItemIsPublic($item))
                ->map(fn (CmsMenuItem $item): array => $this->menuItemPayload($item))
                ->values()
                ->all();
        });

        return response()->json(['data' => $links])->header('Cache-Control', 'public, max-age=60, s-maxage=600');
    }

    public function settings(): JsonResponse
    {
        $settings = Cache::remember('cms.settings.public', now()->addMinutes(10), fn (): array => CmsSetting::query()
            ->where('is_public', true)
            ->get()
            ->filter(fn (CmsSetting $setting): bool => $setting->published_value !== null)
            ->mapWithKeys(fn (CmsSetting $setting): array => [$setting->key => $setting->published_value['value'] ?? null])
            ->all());
        $settings['turnstile_site_key'] = $settings['turnstile_site_key'] ?? config('cms.turnstile_site_key');
        $settings['service_areas'] = config('crm.dhaka_zones', []);

        if (! empty($settings['logo_media_id'])) {
            $settings['logo'] = $this->mediaObject((int) $settings['logo_media_id']);
            unset($settings['logo_media_id']);
        }

        return response()->json(['data' => $settings])->header('Cache-Control', 'public, max-age=60, s-maxage=600');
    }

    public function submitLead(Request $request, LeadService $leadService): JsonResponse
    {
        $base = $request->validate([
            'page_slug' => ['required', 'string', 'max:120'],
            'data' => ['required', 'array'],
            'consent' => ['required', 'accepted'],
            'captcha_token' => ['required', 'string', 'max:4096'],
        ]);

        $page = CmsPage::query()->where('status', 'Published')->where('published_slug', $base['page_slug'])->firstOrFail();
        $snapshot = $page->published_metadata ?? [];
        $form = collect($snapshot['sections'] ?? [])->first(fn (array $section): bool => ($section['type'] ?? null) === 'dynamic_lead_form' && ($section['is_active'] ?? false));
        abort_unless($form, 404, 'This page has no published inquiry form.');

        $fieldDefinitions = data_get($form, 'content.fields', []);
        $allowedFields = ['full_name', 'phone', 'email', 'service_interest', 'area'];
        $configuredFields = collect($fieldDefinitions)->pluck('key')->filter(fn ($key): bool => in_array($key, $allowedFields, true))->unique()->values();
        abort_unless($configuredFields->contains('full_name') && $configuredFields->contains('phone'), 422, 'The published form is missing required contact fields.');

        $rules = ['data' => ['required', 'array:'.$configuredFields->implode(',')]];
        foreach ($fieldDefinitions as $field) {
            $key = $field['key'] ?? null;
            if (! in_array($key, $allowedFields, true)) {
                continue;
            }
            $required = in_array($key, ['full_name', 'phone'], true) || (bool) ($field['required'] ?? false);
            $presence = $required ? 'required' : 'nullable';
            $rules['data.'.$key] = match ($key) {
                'full_name' => [$presence, 'string', 'min:2', 'max:100'],
                'phone' => [$presence, 'string', 'max:20'],
                'email' => [$presence, 'email', 'max:254'],
                'service_interest' => [$presence, 'string', 'max:500'],
                'area' => [$presence, Rule::in(config('crm.dhaka_zones'))],
            };
        }
        $validated = $request->validate($rules);

        $this->verifyCaptcha($base['captcha_token'], $request);
        $consentCopy = trim((string) data_get(CmsSetting::query()->where('key', 'lead_form_consent_text')->first()?->published_value, 'value'));
        if ($consentCopy === '') {
            abort(503, 'Website inquiry consent text has not been configured and published.');
        }
        $data = $validated['data'];
        $normalizedPhone = $leadService->normalizePhone($data['phone']);
        $duplicates = $leadService->detectDuplicates($normalizedPhone, $data['email'] ?? null);
        if ($duplicates->isNotEmpty()) {
            return response()->json(['message' => 'An inquiry with these details may already exist. Please contact our team directly.'], 409);
        }

        $source = 'website:'.$page->published_slug;
        $consentSource = $source.':'.substr(hash('sha256', $consentCopy), 0, 32);
        $lead = DB::transaction(function () use ($data, $normalizedPhone, $source, $consentSource): Lead {
            $lead = Lead::query()->create([
                'full_name' => trim($data['full_name']),
                'phone' => trim($data['phone']),
                'normalized_phone' => $normalizedPhone,
                'email' => isset($data['email']) ? strtolower(trim($data['email'])) : null,
                'channel' => 'Web Form',
                'source' => $source,
                'service_interest' => $data['service_interest'] ?? null,
                'area' => $data['area'] ?? null,
                'status' => 'New',
                'consent_status' => 'Granted',
                'is_active' => true,
            ]);

            LeadConsentRecord::query()->create([
                'lead_id' => $lead->id,
                'channel' => 'Web Form',
                'status' => 'Granted',
                'source' => $consentSource,
                'consented_at' => now(),
            ]);
            activity()->performedOn($lead)->withProperties(['channel' => 'Web Form', 'source' => $source])->log('Website inquiry received');

            return $lead;
        });

        return response()->json(['message' => 'Thank you. Your inquiry has been received.'], 201);
    }

    private function verifyCaptcha(string $token, Request $request): void
    {
        $secret = config('cms.turnstile_secret');
        if (! is_string($secret) || $secret === '') {
            abort(503, 'Website form verification is not configured.');
        }

        try {
            $response = Http::asForm()->timeout(5)->post(config('cms.turnstile_verify_url'), [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (\Throwable) {
            abort(503, 'Website form verification is temporarily unavailable.');
        }

        abort_unless($response->successful() && $response->json('success') === true, 422, 'CAPTCHA verification failed.');
    }

    /** @param array<string, mixed> $content
     *  @return array<string, mixed>
     */
    private function resolveMediaReferences(array $content): array
    {
        foreach ($content as $key => $value) {
            if (in_array($key, ['media_id', 'background_media_id'], true)) {
                $content[$key] = $this->mediaObject($value ? (int) $value : null);
            } elseif (is_array($value)) {
                $content[$key] = array_is_list($value)
                    ? array_map(fn ($item) => is_array($item) ? $this->resolveMediaReferences($item) : $item, $value)
                    : $this->resolveMediaReferences($value);
            }
        }

        return $content;
    }

    private function mediaObject(?int $mediaId): ?array
    {
        if (! $mediaId) {
            return null;
        }
        $media = CmsMedia::query()->where('is_public', true)->find($mediaId);
        if (! $media) {
            return null;
        }

        return ['id' => $media->id, 'url' => $media->url(), 'alt' => $media->alt_text, 'mime_type' => $media->mime_type];
    }

    /** @param array<string, mixed> $content
     *  @return array<int, array<string, mixed>>
     */
    private function serviceGrid(array $content): array
    {
        return Service::query()->where('is_active', true)->orderBy('name')->limit(max(1, min(12, (int) ($content['limit'] ?? 4))))->get()->map(function (Service $service) use ($content): array {
            $price = $service->currentPrice();

            return [
                'code' => $service->code,
                'name' => $service->name,
                'description' => $service->description,
                'variant' => $service->variant,
                'price' => ! empty($content['show_prices']) && $price ? [
                    'amount' => (string) $price->amount,
                    'formatted' => '৳ '. $this->formatBdt((string) $price->getRawOriginal('amount')),
                    'unit' => $price->unit,
                    'price_type' => $price->price_type,
                ] : null,
            ];
        })->all();
    }

    private function formatBdt(string $amount): string
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            return $amount;
        }

        return number_format((int) $matches[1], 0, '.', ',').'.'.str_pad($matches[2] ?? '', 2, '0');
    }

    private function menuItemIsPublic(CmsMenuItem $item): bool
    {
        return ! $item->cms_page_id || ($item->page && $item->page->status === 'Published' && $item->page->published_slug);
    }

    /** @return array<string, mixed> */
    private function menuItemPayload(CmsMenuItem $item): array
    {
        return [
            'label' => $item->label,
            'url' => $item->cms_page_id ? '/'.$item->page->published_slug : $item->url,
            'external' => $item->is_external,
            'opens_new_tab' => $item->opens_new_tab,
            'children' => $item->children
                ->filter(fn (CmsMenuItem $child): bool => $this->menuItemIsPublic($child))
                ->map(fn (CmsMenuItem $child): array => [
                    'label' => $child->label,
                    'url' => $child->cms_page_id ? '/'.$child->page->published_slug : $child->url,
                    'external' => $child->is_external,
                    'opens_new_tab' => $child->opens_new_tab,
                ])->values()->all(),
        ];
    }
}
