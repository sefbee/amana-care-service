@props([
    'title' => null,
])
<div class="card">
    @isset($title)
        <div class="card-header">
            <h3 class="card-title">{{ $title }}</h3>
            {{-- <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-light">
                    Action
                </button>
            </div> --}}
        </div>
    @endisset
    <div class="card-body py-5">
        {{ $slot }}
    </div>
</div>
