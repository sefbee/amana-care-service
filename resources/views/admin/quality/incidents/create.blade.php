@extends('layouts.base')
@section('title', 'Report Serious Incident')
@php($breadcrumb = [['title' => 'Quality & Support'], ['title' => 'Report Serious Incident']])
@section('content')
<div class="row"><div class="col-md-8 offset-md-2">
    <x-alert :show_validations="false" />
    @if (session('restricted_banner'))
        <div class="alert alert-danger" role="alert">{{ session('restricted_banner') }}</div>
    @endif
    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('safeguarding.incidents.store') }}">
            @csrf
            <div class="mb-3"><label for="category" class="form-label">Incident category</label><input id="category" name="category" class="form-control" maxlength="80" value="{{ old('category') }}" required></div>
            <div class="mb-3"><label for="summary" class="form-label">Brief summary</label><input id="summary" name="summary" class="form-control" maxlength="500" value="{{ old('summary') }}" required></div>
            <div class="mb-3"><label for="details" class="form-label">Restricted incident report</label><textarea id="details" name="details" class="form-control" rows="7" maxlength="12000" required>{{ old('details') }}</textarea><div class="form-text">This report is encrypted and visible only to the Safeguarding Lead and audited Platform Superuser.</div></div>
            <div class="mb-3"><label for="occurred_at" class="form-label">When did it occur? (optional)</label><input id="occurred_at" name="occurred_at" type="datetime-local" class="form-control" value="{{ old('occurred_at') }}"></div>
            <div class="row">
                <div class="col-md-4 mb-3"><label for="household_id" class="form-label">Household ID (optional)</label><input id="household_id" name="household_id" type="number" min="1" class="form-control" value="{{ old('household_id') }}"></div>
                <div class="col-md-4 mb-3"><label for="placement_id" class="form-label">Placement ID (optional)</label><input id="placement_id" name="placement_id" type="number" min="1" class="form-control" value="{{ old('placement_id') }}"></div>
                <div class="col-md-4 mb-3"><label for="caregiver_id" class="form-label">Caregiver ID (optional)</label><input id="caregiver_id" name="caregiver_id" type="number" min="1" class="form-control" value="{{ old('caregiver_id') }}"></div>
            </div>
            <div class="d-flex gap-2"><button class="btn btn-danger" type="submit">Submit restricted report</button><a class="btn btn-light" href="{{ route('dashboard') }}">Cancel</a></div>
        </form>
    </div></div>
</div></div>
@endsection
