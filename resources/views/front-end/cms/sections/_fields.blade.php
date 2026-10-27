@php
    $values = old($prefix, $content ?? []);
@endphp
@foreach($schemaFields as $key => $field)
    @php
        $name = $prefix.'['.$key.']';
        $value = data_get($values, $key, $field['default'] ?? null);
        $fieldId = str_replace(['[', ']'], '_', $name);
    @endphp
    @if($field['type'] === 'repeater')
        @php $rows = is_array($value) ? array_values($value) : []; @endphp
        <div class="mb-4" data-repeater data-next-index="{{ count($rows) }}">
            <label class="form-label fw-semibold">{{ $field['label'] }}</label>
            <div class="cms-repeater-rows">
                @foreach($rows as $index => $row)
                    <div class="border rounded p-3 mb-3 cms-repeater-row">
                        @foreach($field['fields'] as $subKey => $subField)
                            @include('front-end.cms.sections._input', ['field' => $subField, 'name' => $name.'['.$index.']['.$subKey.']', 'id' => $fieldId.'_'.$index.'_'.$subKey, 'value' => $row[$subKey] ?? null, 'media' => $media ?? collect()])
                        @endforeach
                        <button type="button" class="btn btn-light-danger btn-sm" data-remove-row>Remove {{ strtolower($field['item_label'] ?? 'item') }}</button>
                    </div>
                @endforeach
            </div>
            <template data-row-template>
                <div class="border rounded p-3 mb-3 cms-repeater-row">
                    @foreach($field['fields'] as $subKey => $subField)
                        @include('front-end.cms.sections._input', ['field' => $subField, 'name' => $name.'[__INDEX__]['.$subKey.']', 'id' => $fieldId.'___INDEX___'.$subKey, 'value' => null, 'media' => $media ?? collect()])
                    @endforeach
                    <button type="button" class="btn btn-light-danger btn-sm" data-remove-row>Remove {{ strtolower($field['item_label'] ?? 'item') }}</button>
                </div>
            </template>
            <button type="button" class="btn btn-light-primary btn-sm" data-add-row>Add {{ strtolower($field['item_label'] ?? 'item') }}</button>
            @error($prefix.'.'.$key)<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    @else
        @include('front-end.cms.sections._input', ['field' => $field, 'name' => $name, 'id' => $fieldId, 'value' => $value, 'media' => $media ?? collect()])
    @endif
@endforeach
