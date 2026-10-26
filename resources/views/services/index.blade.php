@extends('layouts.base')
@section('title', 'Service Catalog')

@php
    $breadcrumb = [
        ['title' => 'Catalog'],
        ['title' => 'Services'],
    ];
@endphp

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="mb-2">
            <a href="{{ route('services.create') }}" class="btn btn-sm fw-bold btn-primary">Add Service</a>
        </div>

        <div class="card">
            <div class="card-body">
                @php
                    $columns = [
                        ['data' => 'id',         'name' => 'id',         'visible' => false, 'searchable' => false],
                        ['data' => 'name',        'name' => 'name',       'th' => 'Service'],
                        ['data' => 'variant',     'name' => 'variant',    'orderable' => false, 'searchable' => false],
                        ['data' => 'status',      'name' => 'is_active',  'searchable' => false, 'th' => 'Active'],
                        ['data' => 'created_at',  'name' => 'created_at', 'searchable' => false],
                        ['data' => 'action',      'name' => 'action'],
                    ];
                    $url = route('services.index');
                @endphp
                <x-datatable :columns="$columns" :url="$url" id="services_table" />
            </div>
        </div>
    </div>
</div>
@endsection
