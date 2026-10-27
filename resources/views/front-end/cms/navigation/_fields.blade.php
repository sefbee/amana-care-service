<div class="col-md-2">
    <label class="form-label">Location</label>
    <select name="location" class="form-select" required>
        <option value="header" @selected(old('location', $item?->location ?? 'header') === 'header')>Header</option>
        <option value="footer" @selected(old('location', $item?->location ?? 'header') === 'footer')>Footer</option>
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">Label</label>
    <input type="text" name="label" maxlength="100" required value="{{ old('label', $item?->label) }}" class="form-control">
</div>
<div class="col-md-3">
    <label class="form-label">Published page</label>
    <select name="cms_page_id" class="form-select">
        <option value="">Use a URL below</option>
        @foreach($pages as $page)
            <option value="{{ $page->id }}" @selected((string) old('cms_page_id', $item?->cms_page_id) === (string) $page->id)>{{ $page->title }} (/{{ $page->published_slug }})</option>
        @endforeach
    </select>
</div>
<div class="col-md-4">
    <label class="form-label">URL for non-page link</label>
    <input type="text" name="url" maxlength="500" value="{{ old('url', $item?->url) }}" class="form-control" placeholder="/contact or https://example.com">
</div>
<div class="col-md-3">
    <label class="form-label">Parent (optional)</label>
    <select name="parent_id" class="form-select">
        <option value="">Top-level link</option>
        @foreach($items->where('parent_id', null) as $parent)
            @if($parent->id !== $item?->id)
                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $item?->parent_id) === (string) $parent->id)>{{ ucfirst($parent->location) }} · {{ $parent->label }}</option>
            @endif
        @endforeach
    </select>
</div>
<div class="col-md-9 d-flex flex-wrap align-items-center gap-4">
    <input type="hidden" name="is_external" value="0">
    <label class="form-check"><input class="form-check-input" type="checkbox" name="is_external" value="1" @checked(old('is_external', $item?->is_external ?? false))><span class="form-check-label">External, email, or phone link</span></label>
    <input type="hidden" name="opens_new_tab" value="0">
    <label class="form-check"><input class="form-check-input" type="checkbox" name="opens_new_tab" value="1" @checked(old('opens_new_tab', $item?->opens_new_tab ?? false))><span class="form-check-label">Open new tab</span></label>
    <input type="hidden" name="is_visible" value="0">
    <label class="form-check"><input class="form-check-input" type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $item?->is_visible ?? true))><span class="form-check-label">Visible on public site</span></label>
</div>
