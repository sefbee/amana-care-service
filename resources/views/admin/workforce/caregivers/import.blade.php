@extends('layouts.base')
@section('title', 'Import Historical Caregivers')
@php($breadcrumb = [['title' => 'Caregiver Recruitment', 'url' => route('caregivers.index')], ['title' => 'Import Historical CSV']])
@section('actions')
    <a href="{{ route('caregivers.import.template') }}" class="btn btn-sm btn-light-primary">Download CSV template</a>
    <a href="{{ route('caregivers.index') }}" class="btn btn-sm btn-light">Back to candidates</a>
@endsection
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <x-alert :show_validations="false" />

        @if(session()->has('imported_count'))
            <div class="alert alert-success">{{ session('imported_count') }} caregiver record(s) imported. {{ session('import_error_count') }} row(s) need correction.</div>
            @if(session('import_errors'))
                <div class="alert alert-warning"><strong>Import report</strong><ul class="mb-0 mt-2">@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul>
                    @if(session('import_error_count') > count(session('import_errors')))<p class="mb-0 mt-2">Only the first {{ count(session('import_errors')) }} row errors are shown.</p>@endif
                </div>
            @endif
        @endif

        <div class="card shadow-sm">
            <div class="card-header"><h6 class="card-title mb-0 fw-bold">CSV format and safeguards</h6></div>
            <div class="card-body">
                <p>Upload up to 500 caregiver rows as CSV (maximum 5 MB). Use the supplied header template. Separate multiple skills and zones with the <code>|</code> character; skill values must use configured skill keys. Set <code>live_in_preferred</code> to 1 or 0 (true/false and yes/no are also accepted).</p>
                <p>Historical outcomes are retained as claims with <strong>Needs Verification</strong> status. Imported checks have no verified evidence, cannot be approved, and never satisfy readiness. Upload clean evidence and record a new check through the caregiver profile. Imported caregivers always start Not Ready.</p>
                <p class="small text-muted">National ID is used only to create the protected duplicate fingerprint. It is not stored in the database, import report, activity log, or validation error session. Keep the source file in your approved secure location and delete local working copies according to the approved retention policy.</p>
                <form method="POST" enctype="multipart/form-data" action="{{ route('caregivers.import.store') }}">
                    @csrf
                    <div class="mb-4"><label class="form-label" for="caregiver-csv">Historical caregiver CSV</label><input id="caregiver-csv" class="form-control" type="file" name="file" accept=".csv,text/csv" required></div>
                    <div class="d-flex gap-2"><button class="btn btn-primary" type="submit">Import CSV</button><a class="btn btn-light" href="{{ route('caregivers.index') }}">Cancel</a></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
