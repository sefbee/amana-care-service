@extends('layouts.base')
@section('title', 'Caregiver Recruitment')
@php($breadcrumb = [['title' => 'Caregiver Recruitment']])
@section('actions')
    @if(auth()->user()->hasPermission('CaregiverController@importForm'))
        <a href="{{ route('caregivers.import.form') }}" class="btn btn-sm btn-light-primary">Import historical CSV</a>
    @endif
    @if(auth()->user()->hasPermission('CaregiverController@create'))
        <a href="{{ route('caregivers.create') }}" class="btn btn-sm btn-primary">Add Candidate</a>
    @endif
@endsection
@section('content')
<div class="row"><div class="col-md-12">
    <div class="card"><div class="card-body">
        @php($columns = [
            ['data' => 'id', 'name' => 'id', 'visible' => false, 'searchable' => false],
            ['data' => 'name', 'name' => 'name', 'th' => 'Candidate'],
            ['data' => 'phone', 'name' => 'phone', 'th' => 'Phone'],
            ['data' => 'recruitment_stage', 'name' => 'recruitment_stage', 'th' => 'Stage'],
            ['data' => 'status', 'name' => 'status', 'th' => 'Readiness'],
            ['data' => 'available_from', 'name' => 'available_from', 'th' => 'Available from'],
            ['data' => 'action', 'name' => 'action', 'searchable' => false, 'orderable' => false],
        ])
        <x-datatable :columns="$columns" :url="route('caregivers.index')" id="caregivers_table" />
    </div></div>
</div></div>
@endsection
