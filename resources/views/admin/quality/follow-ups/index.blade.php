@extends('layouts.base')
@section('title', 'Placement Follow-ups')
@php($breadcrumb = [['title' => 'Quality & Support'], ['title' => 'Placement Follow-ups']])
@section('content')
<div class="row"><div class="col-md-12">
    <x-alert :show_validations="false" />
    <div class="card"><div class="card-body">
        @php($columns = [
            ['data' => 'id', 'name' => 'id', 'visible' => false, 'searchable' => false],
            ['data' => 'household', 'name' => 'household', 'th' => 'Household', 'orderable' => false],
            ['data' => 'caregiver', 'name' => 'caregiver', 'th' => 'Caregiver', 'orderable' => false],
            ['data' => 'milestone', 'name' => 'milestone_day', 'th' => 'Follow-up'],
            ['data' => 'due_at', 'name' => 'due_at', 'th' => 'Due'],
            ['data' => 'status', 'name' => 'status', 'th' => 'Status'],
            ['data' => 'outcome', 'name' => 'outcome', 'th' => 'Outcome', 'orderable' => false],
            ['data' => 'action', 'name' => 'action', 'searchable' => false, 'orderable' => false],
        ])
        <x-datatable :columns="$columns" :url="route('placement-follow-ups.index')" id="placement_follow_ups_table" />
    </div></div>
</div></div>
@endsection
