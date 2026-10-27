@extends('layouts.base')
@section('title', $lead->full_name)

@php
    $breadcrumb = [
        ['title' => 'CRM'],
        ['title' => 'Leads', 'url' => route('leads.index')],
        ['title' => $lead->full_name],
    ];
@endphp

@section('actions')
    @if ($lead->status === 'Qualified')
        <form action="{{ route('leads.convert', $lead->id) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm fw-bold btn-success" data-confirm="Convert this lead to a Household?">Convert to Household</button>
        </form>
    @endif
    <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-sm fw-bold btn-primary">Edit</a>
@endsection

@section('tabs')
<ul class="nav nav-tabs nav-bold nav-tabs-line mt-3" id="leadTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab_details">Details</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_comm">Communication Log</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_consent">Consent</a></li>
</ul>
@endsection

@section('content')
<div class="tab-content mt-4">

    {{-- DETAILS --}}
    <div class="tab-pane fade show active" id="tab_details">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header"><h6 class="card-title mb-0 fw-bold">Contact</h6></div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr><th class="w-40 text-muted fw-normal">Phone</th><td>{{ $lead->phone }}</td></tr>
                            <tr><th class="text-muted fw-normal">Email</th><td>{{ $lead->email ?? '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">Channel</th><td>{{ $lead->channel }}</td></tr>
                            <tr><th class="text-muted fw-normal">Consent</th><td>{{ $lead->consent_status }}</td></tr>
                            <tr><th class="text-muted fw-normal">Coordinator</th><td>{{ $lead->owner->name ?? '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header"><h6 class="card-title mb-0 fw-bold">Service</h6></div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr><th class="w-40 text-muted fw-normal">Status</th><td>{!! $lead->status_badge !!}</td></tr>
                            <tr><th class="text-muted fw-normal">Area</th><td>{{ $lead->area ?? '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">Live-in Pref</th><td>{{ $lead->live_in_pref ?? '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">Service Interest</th><td>{{ $lead->service_interest ?? '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">Start Date</th><td>{{ $lead->preferred_start_date?->format('d M Y') ?? '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">Budget</th><td>{{ $lead->budget_expectation ? '৳'.number_format($lead->budget_expectation) : '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            @if ($lead->notes)
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header"><h6 class="card-title mb-0 fw-bold">Notes</h6></div>
                    <div class="card-body">
                        <p class="text-muted mb-0">{{ $lead->notes }}</p>
                    </div>
                </div>
            </div>
            @endif
            @if ($lead->statusHistories->isNotEmpty())
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>From</th><th>To</th><th>By</th><th>Date</th></tr></thead>
                            <tbody>
                                @foreach ($lead->statusHistories as $h)
                                <tr>
                                    <td>{{ $h->from_status ?? '—' }}</td>
                                    <td>{{ $h->to_status }}</td>
                                    <td>{{ $h->changedBy->name ?? '—' }}</td>
                                    <td>{{ $h->created_at->format('d M Y h:i a') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- COMMUNICATION LOG --}}
    <div class="tab-pane fade" id="tab_comm">
        @if ($lead->consent_status !== 'Granted')
        <div class="alert alert-warning mb-3">⚠ Outbound messages are blocked — consent not granted. <a href="#tab_consent" data-bs-toggle="tab" data-bs-target="#tab_consent">Update consent →</a></div>
        @endif
        <div class="alert alert-info mb-3">Outbound Email and SMS also require the latest consent record for that exact channel to be Granted.</div>
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h6 class="card-title mb-0 fw-bold">Record communication</h6></div>
                    <div class="card-body">
                        <form action="{{ route('leads.communication-logs.store', $lead->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <x-form-group>
                                        <x-form-label for="contact_date" required="true">Date & Time</x-form-label>
                                        <input type="datetime-local" name="contact_date" class="form-control" required value="{{ old('contact_date', now()->format('Y-m-d\TH:i')) }}">
                                        <x-input-error :messages="$errors->get('contact_date')" class="mt-2" />
                                    </x-form-group>
                                </div>
                                <div class="col-md-4">
                                    <x-form-group>
                                        <x-form-label for="direction" required="true">Direction</x-form-label>
                                        <select name="direction" class="form-select" required>
                                            <option value="Inbound">Inbound</option>
                                            <option value="Outbound">Outbound</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('direction')" class="mt-2" />
                                    </x-form-group>
                                </div>
                                <div class="col-md-4">
                                    <x-form-group>
                                        <x-form-label for="log_channel" required="true">Channel</x-form-label>
                                        <select name="channel" class="form-select" required>
                                            @foreach ($logChannels as $ch)
                                                <option value="{{ $ch }}">{{ $ch }}</option>
                                            @endforeach
                                        </select>
                                    </x-form-group>
                                </div>
                            </div>
                            <x-form-group>
                                <x-form-label for="summary" required="true">Summary</x-form-label>
                                <textarea name="summary" class="form-control" rows="2" maxlength="1000" required>{{ old('summary') }}</textarea>
                                <x-input-error :messages="$errors->get('summary')" class="mt-2" />
                            </x-form-group>
                            <x-form-group>
                                <x-form-label for="next_step">Next Step</x-form-label>
                                <input type="text" name="next_step" class="form-control" maxlength="500" value="{{ old('next_step') }}">
                            </x-form-group>
                            <div class="mt-4 border-top pt-4">
                                <button type="submit" class="btn btn-primary btn-sm">Add Entry</button>
                            </div>
                        </form>
                    </div>
                </div>
                @forelse ($lead->communicationLogs as $log)
                <div class="card shadow-sm mb-2">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge badge-light-{{ $log->direction === 'Inbound' ? 'success' : 'primary' }} me-1">{{ $log->direction }}</span>
                                <span class="badge badge-light-secondary me-2">{{ $log->channel }}</span>
                                <span class="text-muted small">{{ \Carbon\Carbon::parse($log->contact_date)->format('d M Y h:i a') }}</span>
                            </div>
                            @if (auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('Lead\\CommunicationLogController@destroy'))
                                <form action="{{ route('leads.communication-logs.destroy', [$lead->id, $log->id]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-icon btn-light-danger btn-xs" data-confirm="Delete this communication record?"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            @endif
                        </div>
                        <p class="mb-1 mt-2">{{ $log->summary }}</p>
                        @if ($log->next_step)<p class="text-muted small mb-0"><strong>Next:</strong> {{ $log->next_step }}</p>@endif
                        <p class="text-muted small mb-0">By {{ $log->creator->name ?? '—' }}</p>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-4">No communication logs yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- CONSENT --}}
    <div class="tab-pane fade" id="tab_consent">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h6 class="card-title mb-0 fw-bold">Record channel consent</h6></div>
                    <div class="card-body">
                        <form action="{{ route('leads.consents.store', $lead->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <x-form-group>
                                        <x-form-label for="consent_channel" required="true">Channel</x-form-label>
                                        <select name="channel" class="form-select" required>
                                            @foreach ($consentChannels as $ch)
                                                <option value="{{ $ch }}">{{ $ch }}</option>
                                            @endforeach
                                        </select>
                                    </x-form-group>
                                </div>
                                <div class="col-md-4">
                                    <x-form-group>
                                        <x-form-label for="consent_status_f" required="true">Status</x-form-label>
                                        <select name="status" class="form-select" required>
                                            @foreach ($consentStatuses as $cs)
                                                <option value="{{ $cs }}">{{ $cs }}</option>
                                            @endforeach
                                        </select>
                                    </x-form-group>
                                </div>
                                <div class="col-md-4">
                                    <x-form-group>
                                        <x-form-label for="source">Source</x-form-label>
                                        <input type="text" name="source" class="form-control" maxlength="200">
                                    </x-form-group>
                                </div>
                            </div>
                            <div class="mt-4 border-top pt-4">
                                <button type="submit" class="btn btn-primary btn-sm">Save Consent</button>
                            </div>
                        </form>
                    </div>
                </div>
                @forelse ($lead->consentRecords as $record)
                <div class="card shadow-sm mb-2">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge badge-light-{{ $record->status === 'Granted' ? 'success' : ($record->status === 'Withdrawn' ? 'danger' : 'warning') }} me-2">{{ $record->status }}</span>
                            <span class="badge badge-light-secondary me-2">{{ $record->channel }}</span>
                            <span class="text-muted small">{{ $record->created_at->format('d M Y h:i a') }}</span>
                            @if ($record->source)<span class="text-muted small ms-2">— {{ $record->source }}</span>@endif
                        </div>
                        @php($currentForChannel = $lead->consentRecords->where('channel', $record->channel)->sortByDesc('id')->first())
                        @if ($record->status === 'Granted' && $currentForChannel?->is($record))
                        <form action="{{ route('leads.consents.update', [$lead->id, $record->id]) }}" method="POST">
                            @csrf @method('PUT')
                            <button class="btn btn-light-danger btn-sm" data-confirm="Withdraw consent?">Withdraw</button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-4">No consent records yet.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
