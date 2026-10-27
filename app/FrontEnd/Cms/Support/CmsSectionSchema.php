<?php

namespace App\FrontEnd\Cms\Support;

use Illuminate\Validation\Rule;

final class CmsSectionSchema
{
    /** @var array<string, array{label: string, fields: array<string, array<string, mixed>>}> */
    private const SCHEMAS = [
        'hero' => ['label' => 'Hero', 'fields' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'max' => 80],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'max' => 160, 'required' => true],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'max' => 1000],
            'cta_label' => ['label' => 'Button label', 'type' => 'text', 'max' => 60],
            'cta_link' => ['label' => 'Button URL', 'type' => 'url', 'max' => 500],
            'background_media_id' => ['label' => 'Background image', 'type' => 'media'],
        ]],
        'rich_text' => ['label' => 'Rich Text', 'fields' => [
            'html' => ['label' => 'Content', 'type' => 'html', 'max' => 20000, 'required' => true],
        ]],
        'service_grid' => ['label' => 'Service Grid', 'fields' => [
            'title' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
            'body' => ['label' => 'Supporting text', 'type' => 'textarea', 'max' => 1000],
            'limit' => ['label' => 'Services to show', 'type' => 'number', 'min' => 1, 'max' => 12, 'default' => 4],
            'show_prices' => ['label' => 'Show approved prices', 'type' => 'boolean', 'default' => true],
        ]],
        'image_text_split' => ['label' => 'Image / Text Split', 'fields' => [
            'layout_alignment' => ['label' => 'Image placement', 'type' => 'select', 'options' => ['left' => 'Image left', 'right' => 'Image right'], 'default' => 'left'],
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'max' => 80],
            'title' => ['label' => 'Heading', 'type' => 'text', 'max' => 160, 'required' => true],
            'body' => ['label' => 'Text', 'type' => 'textarea', 'max' => 3000],
            'media_id' => ['label' => 'Image', 'type' => 'media', 'required' => true],
            'cta_label' => ['label' => 'Button label', 'type' => 'text', 'max' => 60],
            'cta_link' => ['label' => 'Button URL', 'type' => 'url', 'max' => 500],
        ]],
        'testimonial_carousel' => ['label' => 'Testimonial Carousel', 'fields' => [
            'title' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
            'items' => ['label' => 'Testimonials', 'type' => 'repeater', 'max' => 12, 'item_label' => 'Testimonial', 'fields' => [
                'client_name' => ['label' => 'Name', 'type' => 'text', 'max' => 100, 'required' => true],
                'review_text' => ['label' => 'Review', 'type' => 'textarea', 'max' => 1000, 'required' => true],
                'rating' => ['label' => 'Rating (1–5)', 'type' => 'number', 'min' => 1, 'max' => 5, 'required' => true],
            ]],
        ]],
        'faq_accordion' => ['label' => 'FAQ Accordion', 'fields' => [
            'title' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
            'items' => ['label' => 'Questions', 'type' => 'repeater', 'max' => 30, 'item_label' => 'Question', 'fields' => [
                'question' => ['label' => 'Question', 'type' => 'text', 'max' => 300, 'required' => true],
                'answer' => ['label' => 'Answer', 'type' => 'textarea', 'max' => 3000, 'required' => true],
            ]],
        ]],
        'dynamic_lead_form' => ['label' => 'Dynamic Lead Form', 'fields' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'max' => 80],
            'title' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
            'body' => ['label' => 'Supporting text', 'type' => 'textarea', 'max' => 1000],
            'success_message' => ['label' => 'Success message', 'type' => 'text', 'max' => 300],
            'submit_label' => ['label' => 'Submit button label', 'type' => 'text', 'max' => 60],
            'fields' => ['label' => 'Form fields', 'type' => 'repeater', 'max' => 12, 'item_label' => 'Field', 'fields' => [
                'key' => ['label' => 'Field key', 'type' => 'select', 'options' => [
                    'full_name' => 'Full name', 'phone' => 'Phone', 'email' => 'Email',
                    'service_interest' => 'Service interest', 'area' => 'Area',
                ], 'required' => true],
                'label' => ['label' => 'Field label', 'type' => 'text', 'max' => 100, 'required' => true],
                'required' => ['label' => 'Required', 'type' => 'boolean'],
            ]],
        ]],
    ];

    /** @return array<string, array{label: string, fields: array<string, array<string, mixed>>}> */
    public static function all(): array
    {
        return self::SCHEMAS;
    }

    /** @return array{label: string, fields: array<string, array<string, mixed>>} */
    public static function get(string $type): array
    {
        abort_unless(isset(self::SCHEMAS[$type]), 404, 'Unsupported content section type.');

        return self::SCHEMAS[$type];
    }

    /** @param array<string, array<string, mixed>> $fields
     *  @return array<string, array<int, mixed>>
     */
    public static function rules(array $fields, string $prefix = 'content'): array
    {
        $rules = [$prefix => ['required', 'array:'.implode(',', array_keys($fields))]];

        foreach ($fields as $key => $field) {
            $name = $prefix.'.'.$key;
            if ($field['type'] === 'repeater') {
                $subFields = $field['fields'];
                $rules[$name] = ['nullable', 'array', 'max:'.$field['max']];
                $rules[$name.'.*'] = ['array:'.implode(',', array_keys($subFields))];
                foreach ($subFields as $subKey => $subField) {
                    $rules[$name.'.*.'.$subKey] = self::fieldRules($subField, ! empty($subField['required']));
                }
                continue;
            }

            $rules[$name] = self::fieldRules($field, ! empty($field['required']));
        }

        return $rules;
    }

    /** @param array<string, mixed> $field
     *  @return array<int, mixed>
     */
    private static function fieldRules(array $field, bool $required): array
    {
        $presence = $required ? 'required' : 'nullable';

        return match ($field['type']) {
            'text', 'textarea', 'html' => [$presence, 'string', 'max:'.$field['max']],
            'number' => [$presence, 'integer', 'min:'.$field['min'], 'max:'.$field['max']],
            'boolean' => [$presence, 'boolean'],
            'select' => [$presence, 'string', Rule::in(array_keys($field['options']))],
            'url' => [$presence, 'string', 'max:'.$field['max'], Rule::regex('~^(/(?!/)[^\\s]*|https://[^\\s]+|mailto:[^\\s@]+@[^\\s@]+|tel:\\+?[0-9().-]{7,20})$~')],
            'media' => [$presence, 'integer', Rule::exists('cms_media', 'id')->where('is_public', true)->where('mime_type', 'like', 'image/%')],
            default => ['prohibited'],
        };
    }
}
