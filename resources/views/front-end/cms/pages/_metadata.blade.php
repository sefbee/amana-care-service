<x-form-group>
    <x-form-label for="title" required="true">Page name</x-form-label>
    <input type="text" name="title" id="title" maxlength="150" required class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $page?->title) }}">
    <x-input-error :messages="$errors->get('title')" />
</x-form-group>

<x-form-group>
    <x-form-label for="slug" required="true">URL slug</x-form-label>
    <div class="input-group">
        <span class="input-group-text">/</span>
        <input type="text" name="slug" id="slug" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" required class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $page?->slug) }}">
    </div>
    <x-input-error :messages="$errors->get('slug')" />
</x-form-group>

<div class="card shadow-sm mb-4">
    <div class="card-header"><h6 class="card-title mb-0 fw-bold">Search and sharing preview</h6></div>
    <div class="card-body">
        <x-form-group>
            <x-form-label for="meta_title" required="true">Meta title (up to 60 characters)</x-form-label>
            <input type="text" name="meta_title" id="meta_title" maxlength="60" required class="form-control @error('meta_title') is-invalid @enderror" value="{{ old('meta_title', $page?->meta_title) }}">
            <x-input-error :messages="$errors->get('meta_title')" />
        </x-form-group>
        <x-form-group>
            <x-form-label for="meta_description" required="true">Meta description (up to 160 characters)</x-form-label>
            <textarea name="meta_description" id="meta_description" rows="3" maxlength="160" required class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $page?->meta_description) }}</textarea>
            <x-input-error :messages="$errors->get('meta_description')" />
        </x-form-group>
        <x-form-group>
            <x-form-label for="canonical_url">Canonical URL (HTTPS)</x-form-label>
            <input type="url" name="canonical_url" id="canonical_url" maxlength="500" class="form-control @error('canonical_url') is-invalid @enderror" value="{{ old('canonical_url', $page?->canonical_url) }}">
            <x-input-error :messages="$errors->get('canonical_url')" />
        </x-form-group>
        <x-form-group>
            <x-form-label for="open_graph_media_id">Open Graph image</x-form-label>
            <select name="open_graph_media_id" id="open_graph_media_id" class="form-select">
                <option value="">No sharing image</option>
                @foreach(($media ?? \App\FrontEnd\Cms\Models\CmsMedia::query()->where('is_public', true)->latest()->get()) as $item)
                    @if(str_starts_with($item->mime_type, 'image/'))
                        <option value="{{ $item->id }}" @selected((string) old('open_graph_media_id', $page?->open_graph_media_id) === (string) $item->id)>{{ $item->original_filename }}{{ $item->alt_text ? ' — '.$item->alt_text : '' }}</option>
                    @endif
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('open_graph_media_id')" />
        </x-form-group>
    </div>
</div>
