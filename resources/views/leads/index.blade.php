@extends('layouts.base')
@section('title', 'Leads')

@php
    $breadcrumb = [
        ['title' => 'CRM'],
        ['title' => 'Leads'],
    ];
@endphp

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="mb-2">
            <a href="{{ route('leads.create') }}" class="btn btn-sm fw-bold btn-primary">Add New Lead</a>
            <button type="button" class="btn btn-sm fw-bold btn-info" data-bs-toggle="modal" data-bs-target="#filter_modal">Filter</button>
        </div>
        <div class="card">
            <div class="card-body">
                @php
                    $columns = [
                        ['data' => 'id',        'name' => 'id',        'visible' => false, 'searchable' => false],
                        ['data' => 'full_name',  'name' => 'full_name', 'th' => 'Name'],
                        ['data' => 'phone',      'name' => 'phone',     'orderable' => false],
                        ['data' => 'channel',    'name' => 'channel',   'orderable' => false],
                        ['data' => 'area',       'name' => 'area',      'orderable' => false],
                        ['data' => 'status',     'name' => 'status',    'searchable' => false],
                        ['data' => 'owner',      'name' => 'owner',     'searchable' => false, 'orderable' => false],
                        ['data' => 'created_at', 'name' => 'created_at','searchable' => false],
                        ['data' => 'action',     'name' => 'action'],
                    ];
                    $url = route('leads.index');
                @endphp
                <x-datatable :columns="$columns" :url="$url" id="leads_table" />
            </div>
        </div>
    </div>
</div>

<x-modal id="filter_modal" title="Filter Leads" size="md">
    <form id="filterForm">
        <div class="modal-body">
            <x-form-group>
                <x-form-label for="filter_status">Status</x-form-label>
                <select id="filter_status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </x-form-group>
            <x-form-group>
                <x-form-label for="filter_channel">Channel</x-form-label>
                <select id="filter_channel" class="form-select">
                    <option value="">All Channels</option>
                    @foreach ($channels as $channel)
                        <option value="{{ $channel }}">{{ $channel }}</option>
                    @endforeach
                </select>
            </x-form-group>
            <x-form-group>
                <x-form-label for="filter_area">Area</x-form-label>
                <select id="filter_area" class="form-select">
                    <option value="">All Areas</option>
                    @foreach ($dhakaZones as $zone)
                        <option value="{{ $zone }}">{{ $zone }}</option>
                    @endforeach
                </select>
            </x-form-group>
            <x-form-group>
                <x-form-label for="filter_owner_id">Coordinator</x-form-label>
                <select id="filter_owner_id" class="form-select">
                    <option value="">All Coordinators</option>
                    @foreach ($coordinators as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </x-form-group>
            <div class="row">
                <div class="col-6">
                    <x-form-group>
                        <x-form-label for="filter_date_from">Date From</x-form-label>
                        <input type="date" id="filter_date_from" class="form-control">
                    </x-form-group>
                </div>
                <div class="col-6">
                    <x-form-group>
                        <x-form-label for="filter_date_to">Date To</x-form-label>
                        <input type="date" id="filter_date_to" class="form-control">
                    </x-form-group>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-secondary" id="resetFilter">Reset</button>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>
</x-modal>
@endsection

@push('scripts')
<script>
    var baseUrl = '{{ route('leads.index') }}';
    $('#filterForm').submit(function (e) {
        e.preventDefault();
        var params = new URLSearchParams({
            status: $('#filter_status').val(), channel: $('#filter_channel').val(),
            area: $('#filter_area').val(), owner_id: $('#filter_owner_id').val(),
            date_from: $('#filter_date_from').val(), date_to: $('#filter_date_to').val(),
        });
        var dt = $('#leads_table').DataTable();
        dt.ajax.url(baseUrl + '?' + params.toString()); dt.draw();
        $('#filter_modal').modal('hide');
    });
    $('#resetFilter').on('click', function () {
        $('#filterForm')[0].reset();
        var dt = $('#leads_table').DataTable();
        dt.ajax.url(baseUrl); dt.draw();
        $('#filter_modal').modal('hide');
    });
</script>
@endpush
