@extends('layouts.base')
@section('title', 'Edit Website Page')

@php
    $breadcrumb = [
        ['title' => 'Website', 'url' => route('cms.pages.index')],
        ['title' => 'Pages', 'url' => route('cms.pages.index')],
        ['title' => $page->title],
    ];
@endphp

@section('actions')
    <a href="{{ route('cms.pages.preview', $page) }}" target="_blank" rel="noopener" class="btn btn-light-primary btn-sm">Preview Draft</a>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-md-12">
        <x-alert :show_validations="false" />
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="badge badge-light-{{ $page->status === 'Published' ? 'success' : 'warning' }}">{{ $page->status }}</span>
            @if($page->status === 'Published' && $page->hasUnpublishedChanges())
                <span class="badge badge-light-warning">Draft changes awaiting publication</span>
            @endif
            @if($page->published_slug)
                <a href="{{ route('api.v1.cms.pages.show', $page->published_slug) }}" target="_blank" rel="noopener" class="small">View published content API</a>
            @endif
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="card-title mb-0 fw-bold">Page details</h6></div>
            <div class="card-body">
                <form action="{{ route('cms.pages.update', $page) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('front-end.cms.pages._metadata', ['page' => $page, 'media' => $media])
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Save Draft</button>
                        <a href="{{ route('cms.pages.index') }}" class="btn btn-light">Back to Pages</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="card-title mb-0 fw-bold">Page sections</h6>
                <span class="text-muted small">{{ $sections->count() }} sections · {{ $sections->where('is_active', true)->count() }} shown in draft</span>
            </div>
            <div class="card-body" data-section-list data-reorder-url="{{ $sections->isNotEmpty() ? route('cms.sections.reorder', [$page, $sections->first()]) : '' }}">
                @forelse($sections as $section)
                    @php $schema = $schemas[$section->type]; @endphp
                    <div class="card border mb-4" draggable="true" data-section-id="{{ $section->id }}">
                        <div class="card-header d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <h6 class="card-title mb-0 fw-bold">{{ $schema['label'] }}</h6>
                                <small class="text-muted" data-drag-handle>Drag to reorder · Section {{ $loop->iteration }} · Draft order {{ $section->sort_order }}</small>
                            </div>
                            <div class="d-flex gap-1">
                                <form action="{{ route('cms.sections.reorder', [$page, $section]) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="btn btn-light btn-sm" @disabled($loop->first) aria-label="Move section up">↑</button>
                                </form>
                                <form action="{{ route('cms.sections.reorder', [$page, $section]) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="btn btn-light btn-sm" @disabled($loop->last) aria-label="Move section down">↓</button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('cms.sections.update', [$page, $section]) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="0">
                                <label class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($section->is_active)>
                                    <span class="form-check-label">Show this section in the next published version</span>
                                </label>
                                @include('front-end.cms.sections._fields', ['schemaFields' => $schema['fields'], 'prefix' => 'content', 'content' => $section->content, 'media' => $media])
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary btn-sm">Save Section Draft</button>
                                </div>
                            </form>
                            <form action="{{ route('cms.sections.destroy', [$page, $section]) }}" method="POST" class="mt-2" data-confirm="Remove this section from the draft?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-light-danger btn-sm">Remove Section</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">This page has no sections yet. Add a section to start building its content.</div>
                @endforelse
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="card-title mb-0 fw-bold">Add a section</h6></div>
            <div class="card-body">
                <form method="GET" action="{{ route('cms.pages.edit', $page) }}" class="row align-items-end g-3 mb-3">
                    <div class="col-md-5">
                        <x-form-label for="new_section_type">Section type</x-form-label>
                        <select name="new_section_type" id="new_section_type" class="form-select" data-submit-on-change>
                            <option value="">Choose a section type…</option>
                            @foreach($schemas as $type => $definition)
                                <option value="{{ $type }}" @selected($newSectionType === $type)>{{ $definition['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                @if($newSectionSchema && $newSectionType)
                    <form action="{{ route('cms.sections.store', $page) }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="{{ $newSectionType }}">
                        <input type="hidden" name="is_active" value="0">
                        <label class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <span class="form-check-label">Show this section after publishing</span>
                        </label>
                        @include('front-end.cms.sections._fields', ['schemaFields' => $newSectionSchema['fields'], 'prefix' => 'content', 'content' => [], 'media' => $media])
                        <button type="submit" class="btn btn-primary">Add Section to Draft</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header"><h6 class="card-title mb-0 fw-bold">Publication</h6></div>
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                @if($canPublish)
                    <form action="{{ route('cms.pages.publish', $page) }}" method="POST" data-confirm="Publish the current page and all its section drafts?">
                        @csrf
                        <button type="submit" class="btn btn-success">{{ $page->status === 'Published' ? 'Publish Draft Changes' : 'Publish Page' }}</button>
                    </form>
                    @if($page->status === 'Published')
                        <form action="{{ route('cms.pages.unpublish', $page) }}" method="POST" data-confirm="Unpublish this page?">
                            @csrf
                            <button type="submit" class="btn btn-light-warning" @disabled($navigationReferences > 0)>Unpublish Page</button>
                        </form>
                        @if($navigationReferences > 0)<small class="text-muted">Remove or hide {{ $navigationReferences }} active navigation link(s) first.</small>@endif
                    @endif
                @else
                    <div class="alert alert-info mb-0">Your changes are drafts. An Operations Manager must review and publish them.</div>
                @endif
                <form action="{{ route('cms.pages.destroy', $page) }}" method="POST" class="ms-auto" data-confirm="Delete this page?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-light-danger" @disabled($navigationReferences > 0)>Delete Page</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
document.addEventListener('click', (event) => {
    const add = event.target.closest('[data-add-row]');
    const remove = event.target.closest('[data-remove-row]');
    if (add) {
        const group = add.closest('[data-repeater]');
        const template = group.querySelector('[data-row-template]');
        const index = Number(group.dataset.nextIndex || 0);
        const row = template.content.cloneNode(true);
        const wrapper = document.createElement('div');
        wrapper.append(row);
        group.querySelector('.cms-repeater-rows').append(wrapper.firstElementChild);
        const added = group.querySelector('.cms-repeater-rows').lastElementChild;
        added.innerHTML = added.innerHTML.replaceAll('__INDEX__', String(index));
        group.dataset.nextIndex = String(index + 1);
    }
    if (remove) remove.closest('.cms-repeater-row')?.remove();
});

let draggedSection = null;
document.addEventListener('dragstart', (event) => {
    draggedSection = event.target.closest('[data-section-id]');
    if (draggedSection) event.dataTransfer.effectAllowed = 'move';
});
document.addEventListener('dragover', (event) => {
    const target = event.target.closest('[data-section-id]');
    if (draggedSection && target && target !== draggedSection) event.preventDefault();
});
document.addEventListener('drop', async (event) => {
    const target = event.target.closest('[data-section-id]');
    if (!draggedSection || !target || target === draggedSection) return;
    event.preventDefault();
    const list = target.closest('[data-section-list]');
    const after = event.clientY > target.getBoundingClientRect().top + target.offsetHeight / 2;
    target.parentNode.insertBefore(draggedSection, after ? target.nextSibling : target);
    const ids = [...list.querySelectorAll('[data-section-id]')].map((node) => Number(node.dataset.sectionId));
    const response = await fetch(list.dataset.reorderUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
        body: JSON.stringify({section_ids: ids}),
    });
    if (response.ok) window.location.reload();
    else window.alert('The section order could not be saved. Reload the page and try again.');
});
</script>
@endpush
@endsection
