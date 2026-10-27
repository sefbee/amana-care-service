@props(['href'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'dropdown-item']) }}>{{ $slot }}</a>
