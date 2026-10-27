<?php

namespace App\FrontEnd\Cms\Support;

use Illuminate\Validation\Rule;

final class CmsSettingSchema
{
    /** @return array<string, array{label: string, type: string, max?: int, public: bool}> */
    public static function fields(): array
    {
        return [
            'site_name' => ['label' => 'Website name', 'type' => 'text', 'max' => 120, 'public' => true],
            'tagline' => ['label' => 'Tagline', 'type' => 'text', 'max' => 200, 'public' => true],
            'support_phone' => ['label' => 'Support phone', 'type' => 'text', 'max' => 40, 'public' => true],
            'call_button_label' => ['label' => 'Header call button label', 'type' => 'text', 'max' => 60, 'public' => true],
            'support_email' => ['label' => 'Support email', 'type' => 'email', 'max' => 254, 'public' => true],
            'support_address' => ['label' => 'Support address', 'type' => 'textarea', 'max' => 500, 'public' => true],
            'whatsapp_url' => ['label' => 'WhatsApp URL', 'type' => 'url', 'max' => 500, 'public' => true],
            'whatsapp_button_label' => ['label' => 'WhatsApp button label', 'type' => 'text', 'max' => 60, 'public' => true],
            'logo_media_id' => ['label' => 'Brand logo', 'type' => 'media', 'public' => true],
            'turnstile_site_key' => ['label' => 'Turnstile site key', 'type' => 'text', 'max' => 300, 'public' => true],
            'lead_form_consent_text' => ['label' => 'Website inquiry consent text', 'type' => 'textarea', 'max' => 500, 'public' => true],
            'primary_color' => ['label' => 'Primary theme color', 'type' => 'color', 'public' => true],
            'secondary_color' => ['label' => 'Secondary theme color', 'type' => 'color', 'public' => true],
            'footer_about' => ['label' => 'Footer description', 'type' => 'textarea', 'max' => 1000, 'public' => true],
            'footer_navigation_heading' => ['label' => 'Footer navigation heading', 'type' => 'text', 'max' => 100, 'public' => true],
            'footer_contact_heading' => ['label' => 'Footer contact heading', 'type' => 'text', 'max' => 100, 'public' => true],
            'footer_copyright' => ['label' => 'Footer copyright text', 'type' => 'text', 'max' => 250, 'public' => true],
            'facebook_url' => ['label' => 'Facebook URL', 'type' => 'url', 'max' => 500, 'public' => true],
            'instagram_url' => ['label' => 'Instagram URL', 'type' => 'url', 'max' => 500, 'public' => true],
            'linkedin_url' => ['label' => 'LinkedIn URL', 'type' => 'url', 'max' => 500, 'public' => true],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        $rules = ['settings' => ['required', 'array:'.implode(',', array_keys(self::fields()))]];
        foreach (self::fields() as $key => $field) {
            $rules['settings.'.$key] = match ($field['type']) {
                'email' => ['nullable', 'email', 'max:'.$field['max']],
                'url' => ['nullable', 'url', 'regex:~^https://~i', 'max:'.$field['max']],
                'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'media' => ['nullable', 'integer', Rule::exists('cms_media', 'id')->where('is_public', true)->where('mime_type', 'like', 'image/%')],
                'textarea' => ['nullable', 'string', 'max:'.$field['max']],
                default => ['nullable', 'string', 'max:'.$field['max']],
            };
        }

        return $rules;
    }
}
