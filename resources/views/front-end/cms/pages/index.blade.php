@extends('layouts.base')
@section('title', 'Website Pages')

@php
    $breadcrumb = [
        ['title' => 'Website', 'url' => route('cms.pages.index')],
        ['title' => 'Pages'],
    ];
@endphp

@section('actions')
    <a href="{{ route('cms.pages.create') }}" class="btn btn-primary btn-sm">Add Page</a>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-body">
                @php
                    $columns = [
                        ['data' => 'id', 'name' => 'id', 'visible' => false, 'searchable' => false],
                        ['data' => 'title', 'name' => 'title', 'th' => 'Page'],
                        ['data' => 'slug', 'name' => 'slug', 'th' => 'Slug'],
                        ['data' => 'status', 'name' => 'status', 'th' => 'Publication'],
                        ['data' => 'updated_at', 'name' => 'updated_at', 'th' => 'Updated'],
                        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
                    ];
                    $url = route('cms.pages.index');
                @endphp
                <x-datatable :columns="$columns" :url="$url" id="cms_pages_table" />
            </div>
        </div>
    </div>
</div>
@endsection
