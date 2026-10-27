@extends('layouts.base')
@section('title', 'Website Navigation')

@php
    $breadcrumb = [
        ['title' => 'Website', 'url' => route('cms.pages.index')],
        ['title' => 'Navigation'],
    ];
@endphp

@section('content')
<div class="row g-4">
    <div class="col-md-12">
        <x-alert :show_validations="false" />
        <div class="alert alert-info">Header and footer links are published immediately by an Operations Manager. CMS page links can target published pages only.</div>
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="card-title mb-0 fw-bold">Add navigation link</h6></div>
            <div class="card-body">
                <form action="{{ route('cms.navigation.store') }}" method="POST" class="row g-3">
                    @csrf
                    @include('front-end.cms.navigation._fields', ['item' => null, 'pages' => $pages])
                    <div class="col-md-12"><button type="submit" class="btn btn-primary">Add link</button></div>
                </form>
            </div>
        </div>
        @foreach(['header' => 'Header navigation', 'footer' => 'Footer navigation'] as $location => $label)
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="card-title mb-0 fw-bold">{{ $label }}</h6></div>
                <div class="card-body">
                    @php $locationItems = $items->where('location', $location); @endphp
                    @forelse($locationItems as $item)
                        <div class="border rounded p-3 mb-3">
                            <form action="{{ route('cms.navigation.update', $item) }}" method="POST" class="row g-3 align-items-end">
                                @csrf
                                @method('PUT')
                                @include('front-end.cms.navigation._fields', ['item' => $item, 'pages' => $pages])
                                <div class="col-md-12 d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-light-primary btn-sm">Save link</button>
                                </div>
                            </form>
                            <div class="d-flex gap-2 mt-2">
                                <form action="{{ route('cms.navigation.reorder', $item) }}" method="POST">
                                    @csrf<input type="hidden" name="direction" value="up">
                                    <button class="btn btn-light btn-sm" aria-label="Move up">↑</button>
                                </form>
                                <form action="{{ route('cms.navigation.reorder', $item) }}" method="POST">
                                    @csrf<input type="hidden" name="direction" value="down">
                                    <button class="btn btn-light btn-sm" aria-label="Move down">↓</button>
                                </form>
                                <form action="{{ route('cms.navigation.destroy', $item) }}" method="POST" data-confirm="Delete this navigation link?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-light-danger btn-sm">Delete</button>
                                </form>
                            </div>
                            @foreach($item->children as $child)
                                <div class="border-start border-3 ps-3 mt-3 ms-3">
                                    <div class="small text-muted mb-2">Child link</div>
                                    <form action="{{ route('cms.navigation.update', $child) }}" method="POST" class="row g-3 align-items-end">
                                        @csrf @method('PUT')
                                        @include('front-end.cms.navigation._fields', ['item' => $child, 'pages' => $pages])
                                        <div class="col-md-12 d-flex flex-wrap gap-2">
                                            <button type="submit" class="btn btn-light-primary btn-sm">Save child link</button>
                                        </div>
                                    </form>
                                    <form action="{{ route('cms.navigation.destroy', $child) }}" method="POST" class="mt-2" data-confirm="Delete this child link?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-light-danger btn-sm">Delete child</button>
                                    </form>
                                    <div class="d-flex gap-2 mt-2">
                                        <form action="{{ route('cms.navigation.reorder', $child) }}" method="POST">
                                            @csrf<input type="hidden" name="direction" value="up">
                                            <button class="btn btn-light btn-sm" aria-label="Move child up">↑</button>
                                        </form>
                                        <form action="{{ route('cms.navigation.reorder', $child) }}" method="POST">
                                            @csrf<input type="hidden" name="direction" value="down">
                                            <button class="btn btn-light btn-sm" aria-label="Move child down">↓</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="text-muted text-center py-4">No {{ strtolower($label) }} links yet.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
