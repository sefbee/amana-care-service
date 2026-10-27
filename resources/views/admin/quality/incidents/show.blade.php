@extends('layouts.base')
@section('title', 'Restricted Safeguarding Case')
@php($breadcrumb = [['title' => 'Quality & Support'], ['title' => 'Restricted Incidents', 'url' => route('safeguarding.incidents.index')], ['title' => 'Restricted Case']])
@section('actions')
    <a href="{{ route('safeguarding.incidents.index') }}" class="btn btn-sm btn-light-primary">Back to restricted queue</a>
@endsection
@section('content')
<div class="row"><div class="col-md-8 offset-md-2">
    <x-alert :show_validations="false" />
    <div class="alert alert-danger" role="alert"><strong>Restricted.</strong> This case is available only to authorized incident readers. Access is audited. Only the Safeguarding Lead can acknowledge the alert or apply a caregiver hold.</div>
    <div class="card shadow-sm mb-4"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Incident report</h6></div><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Classification</dt><dd class="col-sm-8">{{ $incident->classification }}</dd>
            <dt class="col-sm-4">Category</dt><dd class="col-sm-8">{{ $incident->category }}</dd>
            <dt class="col-sm-4">Summary</dt><dd class="col-sm-8">{{ $incident->summary }}</dd>
            <dt class="col-sm-4">Details</dt><dd class="col-sm-8 text-break">{{ $incident->details }}</dd>
            <dt class="col-sm-4">Reported</dt><dd class="col-sm-8">{{ $incident->created_at->format('d M Y, h:i A') }}</dd>
            <dt class="col-sm-4">Occurred</dt><dd class="col-sm-8">{{ $incident->occurred_at?->format('d M Y, h:i A') ?? 'Not provided' }}</dd>
            <dt class="col-sm-4">Reporter</dt><dd class="col-sm-8">{{ $incident->reporter?->name ?? 'Former user' }}</dd>
            <dt class="col-sm-4">Household</dt><dd class="col-sm-8">{{ $incident->household?->display_name ?? ($incident->household_id ? 'Details withheld by conflict-of-interest control' : 'Not linked') }}</dd>
            <dt class="col-sm-4">Caregiver</dt><dd class="col-sm-8">{{ $incident->caregiver?->name ?? ($incident->caregiver_id ? 'Details withheld by conflict-of-interest control' : 'Not linked') }}</dd>
            <dt class="col-sm-4">Placement</dt><dd class="col-sm-8">{{ $canAccessCaregiver && $canAccessHousehold ? ($incident->placement?->id ?? 'Not linked') : 'Details withheld by conflict-of-interest control' }}</dd>
        </dl>
    </div></div>

    @if ($canManageCase && $alert && ! $alert->acknowledged_at)
        <div class="card shadow-sm mb-4"><div class="card-header"><h6 class="card-title mb-0 fw-bold">High-priority alert</h6></div><div class="card-body">
            <p class="mb-3">This alert was created for the Safeguarding Lead. Acknowledge it after reviewing the case.</p>
            @if ($alert->sla_breached_at)<div class="alert alert-danger">The 15-minute acknowledgement target was breached {{ $alert->sla_breached_at->format('d M Y, h:i A') }}.</div>@endif
            <div class="table-responsive mb-3"><table class="table table-sm"><thead><tr><th>Channel</th><th>Status</th><th>Attempts</th><th>Last result</th></tr></thead><tbody>
                @foreach ($alert->deliveries as $delivery)<tr><td>{{ strtoupper($delivery->channel) }}</td><td>{{ $delivery->status }}</td><td>{{ $delivery->attempt_count }}</td><td>{{ $delivery->last_error_code ?? ($delivery->delivered_at?->format('d M Y, h:i A') ?? 'Pending') }}</td></tr>@endforeach
            </tbody></table></div>
            <form method="POST" action="{{ route('safeguarding.incidents.alerts.acknowledge', $alert) }}">@csrf<button class="btn btn-primary">Acknowledge alert</button></form>
        </div></div>
    @elseif ($canManageCase && $alert?->acknowledged_at)
        <div class="alert alert-success">Alert acknowledged {{ $alert->acknowledged_at->format('d M Y, h:i A') }} by {{ $alert->acknowledger?->name ?? 'a Safeguarding Lead' }}.</div>
        @if ($alert->sla_breached_at)<div class="alert alert-danger">The 15-minute acknowledgement target was breached.</div>@endif
    @endif

    @if ($canManageCase && $incident->caregiver_id && $canAccessCaregiver)
        <div class="card shadow-sm"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Caregiver safeguarding action</h6></div><div class="card-body">
            @if ($incident->caregiver_held_at)
                <p class="mb-0">Caregiver is On Hold. Action recorded {{ $incident->caregiver_held_at->format('d M Y, h:i A') }} by {{ $incident->caregiverHoldActor?->name ?? 'Former user' }}.</p>
            @else
                <p>Place the associated caregiver On Hold to revoke Ready status and withdraw draft matching proposals.</p>
                <form method="POST" action="{{ route('safeguarding.incidents.caregiver-hold', $incident) }}">@csrf<button class="btn btn-danger">Place caregiver On Hold</button></form>
            @endif
        </div></div>
    @endif
    @if ($canManageCase && $incident->caregiver_id && ! $canAccessCaregiver)
        <div class="alert alert-secondary">Caregiver details and actions are withheld by conflict-of-interest access control. Another authorized safeguarding lead may review the linked caregiver.</div>
    @endif
</div></div>
@endsection
