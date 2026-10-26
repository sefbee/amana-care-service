@extends('layouts.base')
@section('title', 'Households')

@php
    $breadcrumb = [
        ['title' => 'CRM'],
        ['title' => 'Households'],
    ];
@endphp

@section('actions')
    <a href="{{ route('households.create') }}" class="btn btn-sm fw-bold btn-primary">Add New Household</a>
    <button type="button" class="btn btn-sm fw-bold btn-light ms-2" data-bs-toggle="modal" data-bs-target="#filter_modal">Filter</button>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                @php
                    $columns = [
                        ['data' => 'id',             'name' => 'id',             'visible' => false, 'searchable' => false],
                        ['data' => 'display_name',   'name' => 'display_name',   'th' => 'Name'],
                        ['data' => 'area',            'name' => 'area',           'orderable' => false],
                        ['data' => 'status',          'name' => 'status',         'searchable' => false],
                        ['data' => 'coordinator',     'name' => 'coordinator',    'searchable' => false, 'orderable' => false],
                        ['data' => 'contacts_count',  'name' => 'contacts_count', 'searchable' => false, 'orderable' => false, 'th' => 'Contacts'],
                        ['data' => 'created_at',      'name' => 'created_at',     'searchable' => false],
                        ['data' => 'action',          'name' => 'action'],
                    ];
                    $url = route('households.index');
                @endphp
                <x-datatable :columns="$columns" :url="$url" id="households_table" />
            </div>
        </div>
    </div>
</div>

<x-modal id="filter_modal" title="Filter Households" size="md">
    <form id="filterForm">
        <div class="modal-body">
            <x-form-group>
                <x-form-label for="filter_status">Status</x-form-label>
                <select id="filter_status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
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
                <x-form-label for="filter_coordinator">Coordinator</x-form-label>
                <select id="filter_coordinator" class="form-select">
                    <option value="">All Coordinators</option>
                    @foreach ($coordinators as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </x-form-group>
            <x-form-group>
                <x-form-label for="filter_founding_cohort">Founding Cohort</x-form-label>
                <select id="filter_founding_cohort" class="form-select">
                    <option value="">All</option>
                    <option value="1">Founding Cohort Only</option>
                </select>
            </x-form-group>
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
    var baseUrl = '{{ route('households.index') }}';
    $('#filterForm').submit(function (e) {
        e.preventDefault();
        var params = new URLSearchParams({
            status: $('#filter_status').val(), area: $('#filter_area').val(),
            assigned_coordinator_id: $('#filter_coordinator').val(),
            founding_cohort: $('#filter_founding_cohort').val(),
        });
        var dt = $('#households_table').DataTable();
        dt.ajax.url(baseUrl + '?' + params.toString()); dt.draw();
        $('#filter_modal').modal('hide');
    });
    $('#resetFilter').on('click', function () {
        $('#filterForm')[0].reset();
        var dt = $('#households_table').DataTable();
        dt.ajax.url(baseUrl); dt.draw();
        $('#filter_modal').modal('hide');
    });
</script>
@endpush
