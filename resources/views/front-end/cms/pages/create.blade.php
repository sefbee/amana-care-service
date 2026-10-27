@extends('layouts.base')
@section('title', 'Create Website Page')

@php
    $breadcrumb = [
        ['title' => 'Website', 'url' => route('cms.pages.index')],
        ['title' => 'Pages', 'url' => route('cms.pages.index')],
        ['title' => 'Create Page'],
    ];
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <x-alert :show_validations="false" />
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('cms.pages.store') }}" method="POST">
                    @csrf
                    @include('front-end.cms.pages._metadata', ['page' => null, 'media' => $media])
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Create Draft</button>
                        <a href="{{ route('cms.pages.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
