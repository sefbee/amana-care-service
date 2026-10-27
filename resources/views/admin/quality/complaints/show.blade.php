@extends('layouts.base')
@section('title', 'Complaint '.$complaint->reference)
@php($breadcrumb = [['title' => 'Quality & Support'], ['title' => 'Routine Complaints', 'url' => route('routine-complaints.index')], ['title' => $complaint->reference]])

@section('actions')
    <a href="{{ route('routine-complaints.index') }}" class="btn btn-sm btn-light-primary">Back to complaints</a>
@endsection

@section('content')
    <div class="row g-5">
        <div class="col-md-6"><div class="card h-100"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Complaint details</h6></div><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Reference</dt><dd class="col-sm-8">{{ $complaint->reference }}</dd>
                <dt class="col-sm-4">Subject</dt><dd class="col-sm-8">{{ $complaint->subject }}</dd>
                <dt class="col-sm-4">Household</dt><dd class="col-sm-8">{{ $complaint->household->display_name }}</dd>
                <dt class="col-sm-4">Placement</dt><dd class="col-sm-8">{{ $complaint->placement ? '#'.$complaint->placement->id.' · '.$complaint->placement->status : 'Not linked' }}</dd>
                <dt class="col-sm-4">Reported by</dt><dd class="col-sm-8">{{ $complaint->reporter?->name ?? 'Former user' }}</dd>
                <dt class="col-sm-4">QS owner</dt><dd class="col-sm-8">{{ $complaint->assignee?->name ?? 'Former user' }}</dd>
                <dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ $complaint->status }}</dd>
                <dt class="col-sm-4">Logged</dt><dd class="col-sm-8">{{ $complaint->created_at->format('d M Y, h:i A') }}</dd>
            </dl>
            <hr><h6 class="fw-bold">Details</h6><p class="text-break mb-0">{{ $complaint->details }}</p>
            @if ($complaint->resolution)<hr><h6 class="fw-bold">Resolution</h6><p class="text-break mb-0">{{ $complaint->resolution }}</p><small class="text-muted">Resolved by {{ $complaint->resolver?->name ?? 'Former user' }} on {{ $complaint->resolved_at?->format('d M Y, h:i A') }}</small>@endif
        </div></div></div>

        <div class="col-md-6">
            @if ($complaint->status !== 'Resolved')
                <div class="card mb-5"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Update ownership or status</h6></div><div class="card-body">
                    <form method="POST" action="{{ route('routine-complaints.update', $complaint) }}">
                        @csrf @method('PATCH')
                        <x-form-group><x-form-label for="assigned_to" required="true">QS owner</x-form-label><select class="form-select" id="assigned_to" name="assigned_to" required>@foreach ($assignees as $assignee)<option value="{{ $assignee->id }}" @selected(old('assigned_to', $complaint->assigned_to) == $assignee->id)>{{ $assignee->name }}</option>@endforeach</select></x-form-group>
                        <x-form-group><x-form-label for="status" required="true">Status</x-form-label><select class="form-select" id="status" name="status" required><option value="{{ $complaint->status }}">Keep {{ $complaint->status }}</option>@foreach (['Open' => ['In Progress', 'Escalated'], 'In Progress' => ['Escalated', 'Resolved'], 'Escalated' => ['In Progress', 'Resolved']][$complaint->status] ?? [] as $status)<option value="{{ $status }}" @selected(old('status') === $status)>{{ $status }}</option>@endforeach</select></x-form-group>
                        <x-form-group><x-form-label for="resolution">Resolution note (required to resolve)</x-form-label><textarea class="form-control" id="resolution" name="resolution" rows="3" maxlength="4000">{{ old('resolution') }}</textarea></x-form-group>
                        <x-form-group><x-form-label for="rationale" required="true">Update rationale</x-form-label><textarea class="form-control" id="rationale" name="rationale" rows="3" maxlength="4000" required>{{ old('rationale') }}</textarea></x-form-group>
                        <button class="btn btn-primary">Save update</button>
                    </form>
                </div></div>
            @endif
            @if ($complaint->status === 'Escalated')
                <div class="alert alert-warning">This is a routine complaint escalation. It does not create or replace a Restricted Serious Incident case.</div>
            @endif
            <div class="card"><div class="card-header"><h6 class="card-title mb-0 fw-bold">History</h6></div><div class="card-body">
                <div class="timeline">
                    @foreach ($complaint->events as $event)
                        <div class="mb-4">
                            <div class="fw-bold">{{ $event->event_type === 'StatusChanged' ? ($event->from_value.' → '.$event->to_value) : ($event->event_type === 'Assigned' ? 'Owner changed: '.($event->previousAssignee?->name ?? 'Former user').' → '.($event->nextAssignee?->name ?? 'Former user') : 'Complaint logged') }}</div>
                            <small class="text-muted">{{ $event->actor?->name ?? 'Former user' }} · {{ $event->occurred_at->format('d M Y, h:i A') }}</small>
                            @if ($event->rationale)<p class="mb-0 mt-1">{{ $event->rationale }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </div></div>
        </div>
    </div>
@endsection
