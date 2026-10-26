@props([
    'value',
    'for'=> '',
    'required' => false,
    'class'=> 'form-label'
])

<label class="{{ $class }}" for="{{ $for }}" {{ $attributes }}>
    {{ $slot }}
    @if ($required == "true")
        <span class="text-danger">*</span>
    @endif
</label>
