@extends('layouts.base')
@section('title', 'System Audit Logs')
@php($breadcrumb = [['title' => 'System Audit Logs']])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    @php($columns = [
                        ['data' => 'id', 'name' => 'id', 'visible' => false, 'searchable' => false],
                        ['data' => 'created_at', 'name' => 'created_at', 'th' => 'Timestamp (UTC)', 'searchable' => false],
                        ['data' => 'actor', 'name' => 'causer_id', 'th' => 'Actor', 'searchable' => false, 'orderable' => false],
                        ['data' => 'log_name', 'name' => 'log_name', 'th' => 'Category', 'searchable' => false],
                        ['data' => 'description', 'name' => 'description', 'th' => 'Event', 'searchable' => false, 'orderable' => false],
                        ['data' => 'target', 'name' => 'subject_type', 'th' => 'Target', 'searchable' => false, 'orderable' => false],
                    ])
                    <x-datatable :columns="$columns" :url="route('audit-logs.index')" id="system_audit_logs_table" />
                </div>
            </div>
        </div>
    </div>
@endsection
