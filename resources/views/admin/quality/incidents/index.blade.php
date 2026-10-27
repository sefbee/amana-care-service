@extends('layouts.base')
@section('title', 'Restricted Incidents')
@php($breadcrumb = [['title' => 'Quality & Support'], ['title' => 'Restricted Incidents']])
@section('actions')
    <a href="{{ route('safeguarding.incidents.create') }}" class="btn btn-sm btn-danger">Report serious incident</a>
@endsection
@section('content')
<div class="row"><div class="col-md-12">
    <x-alert :show_validations="false" />
    @if ($unacknowledgedAlerts > 0)<div class="alert alert-danger">{{ $unacknowledgedAlerts }} high-priority safeguarding alert(s) require acknowledgement.</div>@endif
    @if ($deadLetterCount > 0)<div class="alert alert-warning">{{ $deadLetterCount }} email/SMS delivery attempt(s) are in the dead-letter queue and need operational attention.</div>@endif
    <div class="card"><div class="card-body">
        @php($columns = [
            ['data' => 'id', 'name' => 'id', 'visible' => false, 'searchable' => false],
            ['data' => 'category', 'name' => 'category', 'th' => 'Category', 'searchable' => false, 'orderable' => false],
            ['data' => 'household', 'name' => 'household', 'th' => 'Household', 'orderable' => false],
            ['data' => 'caregiver', 'name' => 'caregiver', 'th' => 'Caregiver', 'orderable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'th' => 'Reported'],
            ['data' => 'action', 'name' => 'action', 'searchable' => false, 'orderable' => false],
        ])
        <x-datatable :columns="$columns" :url="route('safeguarding.incidents.index')" id="safeguarding_incidents_table" />
    </div></div>
</div></div>
@endsection
