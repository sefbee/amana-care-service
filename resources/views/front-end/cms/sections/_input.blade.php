@php
    $inputValue = old($name, $value ?? ($field['default'] ?? null));
    $errorName = str_replace(['[', ']'], ['.', ''], $name);
@endphp
<div class="mb-3">
    @if($field['type'] === 'boolean')
        <input type="hidden" name="{{ $name }}" value="0">
        <label class="form-check form-switch">
            <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1" class="form-check-input" @checked((bool) $inputValue)>
            <span class="form-check-label">{{ $field['label'] }}</span>
        </label>
    @else
        <label for="{{ $id }}" class="form-label">{{ $field['label'] }}@if(!empty($field['required'])) <span class="text-danger">*</span>@endif</label>
        @if($field['type'] === 'textarea' || $field['type'] === 'html')
            <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $field['type'] === 'html' ? 8 : 4 }}" maxlength="{{ $field['max'] }}" class="form-control @error($errorName) is-invalid @enderror" @required(!empty($field['required']))>{{ $inputValue }}</textarea>
            @if($field['type'] === 'html')<small class="form-text text-muted">Basic formatting is supported. Scripts, embeds, unsafe links, and styling are removed.</small>@endif
        @elseif($field['type'] === 'select')
            <select name="{{ $name }}" id="{{ $id }}" class="form-select @error($errorName) is-invalid @enderror" @required(!empty($field['required']))>
                <option value="">Select…</option>
                @foreach($field['options'] as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $inputValue === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif($field['type'] === 'media')
            <select name="{{ $name }}" id="{{ $id }}" class="form-select @error($errorName) is-invalid @enderror" @required(!empty($field['required']))>
                <option value="">Select an uploaded image…</option>
                @foreach($media as $item)
                    @if(str_starts_with($item->mime_type, 'image/'))
                        <option value="{{ $item->id }}" @selected((string) $inputValue === (string) $item->id)>{{ $item->original_filename }}{{ $item->alt_text ? ' — '.$item->alt_text : '' }}</option>
                    @endif
                @endforeach
            </select>
        @else
            <input type="{{ $field['type'] === 'number' ? 'number' : 'text' }}" name="{{ $name }}" id="{{ $id }}" value="{{ $inputValue }}" class="form-control @error($errorName) is-invalid @enderror" @if($field['type'] === 'url') pattern="(/(?!/)[^\s]*|https://[^\s]+)" @endif @if(isset($field['max']) && $field['type'] !== 'number') maxlength="{{ $field['max'] }}" @endif @if($field['type'] === 'number') min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="1" @endif @required(!empty($field['required']))>
        @endif
        <x-input-error :messages="$errors->get($errorName)" />
    @endif
</div>
