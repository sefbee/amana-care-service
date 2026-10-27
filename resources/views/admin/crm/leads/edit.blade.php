@extends('layouts.base')
@section('title', 'Edit Lead')

@php
    $breadcrumb = [
        ['title' => 'CRM'],
        ['title' => 'Leads', 'url' => route('leads.index')],
        ['title' => $lead->full_name, 'url' => route('leads.show', $lead->id)],
        ['title' => 'Edit'],
    ];
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <x-alert :show_validations="false" />
        @if (session('duplicate_warning'))
            <div class="alert alert-warning mb-4">
                <strong>Possible duplicate detected.</strong>
                <ul class="mb-2">@foreach (session('duplicates', []) as $duplicate)<li>{{ $duplicate['full_name'] ?? 'Unknown' }} — {{ $duplicate['type'] }} — {{ $duplicate['status'] }}</li>@endforeach</ul>
                <small>Enter a reason to override the match. Linking existing records is not yet available in this workflow.</small>
            </div>
        @endif
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('leads.update', $lead->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="full_name" required="true">Full Name</x-form-label>
                                <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $lead->full_name) }}" maxlength="100" required>
                                <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="phone" required="true">Phone</x-form-label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $lead->phone) }}" required>
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </x-form-group>
                        </div>
                    </div>
                    <x-form-group>
                        <x-form-label for="source" required="true">Source</x-form-label>
                        <input type="text" name="source" class="form-control" value="{{ old('source', $lead->source) }}" maxlength="150" required>
                        <x-input-error :messages="$errors->get('source')" class="mt-2" />
                    </x-form-group>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="email">Email</x-form-label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $lead->email) }}" maxlength="254">
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="channel" required="true">Channel</x-form-label>
                                <select name="channel" class="form-select" required>
                                    @foreach ($channels as $ch)
                                        <option value="{{ $ch }}" {{ old('channel', $lead->channel) === $ch ? 'selected' : '' }}>{{ $ch }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="area">Area</x-form-label>
                                <select name="area" class="form-select">
                                    <option value="">Select Area</option>
                                    @foreach ($dhakaZones as $zone)
                                        <option value="{{ $zone }}" {{ old('area', $lead->area) === $zone ? 'selected' : '' }}>{{ $zone }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="live_in_pref">Live-in Preference</x-form-label>
                                <select name="live_in_pref" class="form-select">
                                    <option value="">Not Specified</option>
                                    @foreach ($liveInPrefs as $pref)
                                        <option value="{{ $pref }}" {{ old('live_in_pref', $lead->live_in_pref) === $pref ? 'selected' : '' }}>{{ ucfirst($pref) }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="status">Status</x-form-label>
                                <select name="status" id="status" class="form-select">
                                    @foreach ($statuses as $s)
                                        <option value="{{ $s }}" {{ old('status', $lead->status) === $s ? 'selected' : '' }}>{{ $s }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="owner_id">Assign Coordinator</x-form-label>
                                <select name="owner_id" class="form-select">
                                    <option value="">Unassigned</option>
                                    @foreach ($coordinators as $c)
                                        <option value="{{ $c->id }}" {{ old('owner_id', $lead->owner_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                    </div>
                    <div id="lost_reason_section" style="{{ in_array(old('status', $lead->status), ['Lost', 'Not Eligible']) ? '' : 'display:none' }}">
                        <x-form-group>
                            <x-form-label for="lost_reason">Reason</x-form-label>
                            <select name="lost_reason" class="form-select">
                                <option value="">Select Reason</option>
                                @foreach ($lostReasons as $reason)
                                    <option value="{{ $reason }}" {{ old('lost_reason', $lead->lost_reason) === $reason ? 'selected' : '' }}>{{ $reason }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('lost_reason')" class="mt-2" />
                        </x-form-group>
                    </div>
                    <x-form-group>
                        <x-form-label for="notes">Notes</x-form-label>
                        <textarea name="notes" class="form-control" rows="3" maxlength="2000">{{ old('notes', $lead->notes) }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </x-form-group>
                    @if (session('duplicate_warning'))
                        <x-form-group>
                            <x-form-label for="override_reason" required="true">Duplicate Override Reason</x-form-label>
                            <input type="text" name="override_reason" class="form-control" value="{{ old('override_reason') }}" maxlength="500" required>
                            <x-input-error :messages="$errors->get('override_reason')" />
                        </x-form-group>
                    @endif
                    <div class="mt-4 border-top pt-5">
                        <button type="submit" class="btn btn-primary">Update Lead</button>
                        <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-light ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    $('#status').on('change', function () {
        var v = $(this).val();
        if (v === 'Lost' || v === 'Not Eligible') { $('#lost_reason_section').show(); }
        else { $('#lost_reason_section').hide(); }
    });
</script>
@endpush
