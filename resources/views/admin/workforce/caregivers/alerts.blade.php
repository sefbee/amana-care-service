@extends('layouts.base')
@section('title', 'Caregiver Readiness Alerts')
@php($breadcrumb = [['title' => 'Caregiver Recruitment', 'url' => route('caregivers.index')], ['title' => 'Readiness Alerts']])
@section('actions')
    <a href="{{ route('caregivers.index') }}" class="btn btn-sm btn-light-primary">Caregiver candidates</a>
@endsection
@section('content')
<div class="row"><div class="col-md-12">
    <x-alert :show_validations="false" />
    <div class="card"><div class="card-body">
        @php($columns = [
            ['data' => 'id', 'name' => 'id', 'visible' => false, 'searchable' => false],
            ['data' => 'candidate', 'name' => 'caregiver.name', 'th' => 'Caregiver'],
            ['data' => 'check_type', 'name' => 'check.type', 'th' => 'Check'],
            ['data' => 'valid_until', 'name' => 'valid_until', 'th' => 'Valid until'],
            ['data' => 'alert_type', 'name' => 'alert_type', 'th' => 'Alert'],
            ['data' => 'status', 'name' => 'read_at', 'th' => 'Status'],
            ['data' => 'action', 'name' => 'action', 'searchable' => false, 'orderable' => false],
        ])
        <x-datatable :columns="$columns" :url="route('caregiver-readiness-alerts.index')" id="caregiver_readiness_alerts_table" />
    </div></div>
</div></div>
@endsection
