@extends('layouts.base')
@section('title', 'Routine Complaints')
@php($breadcrumb = [['title' => 'Quality & Support'], ['title' => 'Routine Complaints']])

@section('actions')
    <a href="{{ route('routine-complaints.create') }}" class="btn btn-sm btn-primary">Log complaint</a>
@endsection

@section('content')
    <div class="row"><div class="col-md-12">
        <x-alert :show_validations="false" />
        <div class="card"><div class="card-body">
            @php($columns = [
                ['data' => 'reference', 'name' => 'reference', 'th' => 'Reference'],
                ['data' => 'subject', 'name' => 'subject', 'th' => 'Subject', 'searchable' => false, 'orderable' => false],
                ['data' => 'household_name', 'name' => 'household.display_name', 'th' => 'Household'],
                ['data' => 'placement_ref', 'name' => 'placement_id', 'th' => 'Placement'],
                ['data' => 'owner', 'name' => 'assignee.name', 'th' => 'QS owner'],
                ['data' => 'status', 'name' => 'status'],
                ['data' => 'created_at', 'name' => 'created_at', 'th' => 'Logged'],
                ['data' => 'action', 'name' => 'action', 'searchable' => false, 'orderable' => false],
            ])
            <x-datatable :columns="$columns" :url="route('routine-complaints.index')" id="routine_complaints_table" />
        </div></div>
    </div></div>
@endsection
