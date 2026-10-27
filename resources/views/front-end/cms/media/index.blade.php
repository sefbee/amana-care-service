@extends('layouts.base')
@section('title', 'Website Media Library')

@php
    $breadcrumb = [
        ['title' => 'Website', 'url' => route('cms.pages.index')],
        ['title' => 'Media Library'],
    ];
@endphp

@section('content')
<div class="row g-4">
    <div class="col-md-12">
        <x-alert :show_validations="false" />
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="card-title mb-0 fw-bold">Upload image or video</h6></div>
            <div class="card-body">
                <form action="{{ route('cms.media.store') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-6">
                        <x-form-label for="file" required="true">File (images up to 10 MB; video up to 50 MB)</x-form-label>
                        <input type="file" name="file" id="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" required class="form-control @error('file') is-invalid @enderror">
                        <x-input-error :messages="$errors->get('file')" />
                    </div>
                    <div class="col-md-4">
                        <x-form-label for="alt_text">Alternative text (required for images)</x-form-label>
                        <input type="text" name="alt_text" id="alt_text" maxlength="200" class="form-control @error('alt_text') is-invalid @enderror" value="{{ old('alt_text') }}">
                        <x-input-error :messages="$errors->get('alt_text')" />
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Upload</button></div>
                </form>
                <small class="text-muted d-block mt-3">Images are converted to WebP when GD supports it; the configured optimizer is used when conversion is unavailable.</small>
            </div>
        </div>

        <div class="row g-4">
            @forelse($mediaItems as $item)
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="card shadow-sm h-100">
                        @if(str_starts_with($item->mime_type, 'image/'))
                            <img src="{{ $item->url() }}" alt="{{ $item->alt_text }}" class="card-img-top" style="height: 180px; object-fit: cover">
                        @else
                            <video src="{{ $item->url() }}" class="card-img-top bg-dark" style="height: 180px; object-fit: contain" controls preload="metadata"></video>
                        @endif
                        <div class="card-body">
                            <div class="fw-semibold text-truncate" title="{{ $item->original_filename }}">{{ $item->original_filename }}</div>
                            <div class="small text-muted">{{ strtoupper(pathinfo($item->path, PATHINFO_EXTENSION)) }} · {{ number_format($item->size_bytes / 1024, 0) }} KB</div>
                            <div class="small text-muted">Uploaded by {{ $item->uploadedBy?->name ?? 'Unknown user' }} · {{ $item->created_at?->format('d M Y') }}</div>
                            <form action="{{ route('cms.media.update', $item) }}" method="POST" class="mt-3">
                                @csrf
                                @method('PUT')
                                <label class="form-label" for="alt-{{ $item->id }}">Alternative text</label>
                                <input type="text" id="alt-{{ $item->id }}" name="alt_text" maxlength="200" value="{{ old('alt_text', $item->alt_text) }}" class="form-control form-control-sm" @required(str_starts_with($item->mime_type, 'image/'))>
                                <input type="hidden" name="is_public" value="0">
                                <label class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_public" value="1" @checked($item->is_public)>
                                    <span class="form-check-label">Available to public pages</span>
                                </label>
                                <button type="submit" class="btn btn-light-primary btn-sm mt-2">Save details</button>
                            </form>
                            <form action="{{ route('cms.media.destroy', $item) }}" method="POST" class="mt-2" data-confirm="Delete this media file?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-light-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-md-12"><div class="card"><div class="card-body text-center text-muted py-5">No website media has been uploaded yet.</div></div></div>
            @endforelse
        </div>
        <div class="mt-4">{{ $mediaItems->links() }}</div>
    </div>
</div>
@endsection
