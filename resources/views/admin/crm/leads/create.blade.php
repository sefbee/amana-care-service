@extends('layouts.base')
@section('title', 'New Lead')

@php
    $breadcrumb = [
        ['title' => 'CRM'],
        ['title' => 'Leads', 'url' => route('leads.index')],
        ['title' => 'New Lead'],
    ];
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <x-alert :show_validations="false" />

        @if (session('duplicate_warning'))
        <div class="alert alert-warning mb-4">
            <h6 class="fw-bold mb-2">⚠ Possible Duplicate Detected</h6>
            <ul class="mb-2">
                @foreach (session('duplicates', []) as $dup)
                    <li><strong>{{ $dup['full_name'] ?? $dup['display_name'] ?? 'Unknown' }}</strong> — {{ $dup['type'] }} — {{ $dup['status'] }}</li>
                @endforeach
            </ul>
            <small class="text-muted">Confirm the match handling by providing a clear reason to override. Linking existing records is not yet available in this workflow.</small>
        </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('leads.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="full_name" required="true">Full Name</x-form-label>
                                <input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name') }}" maxlength="100" required>
                                <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="phone" required="true">Phone</x-form-label>
                                <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" required>
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </x-form-group>
                        </div>
                    </div>
                    <x-form-group>
                        <x-form-label for="source" required="true">Source</x-form-label>
                        <input type="text" name="source" class="form-control" value="{{ old('source') }}" maxlength="150" placeholder="e.g. Referral, Organic Search, Phone" required>
                        <x-input-error :messages="$errors->get('source')" class="mt-2" />
                    </x-form-group>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="email">Email</x-form-label>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" maxlength="254">
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="channel" required="true">Channel</x-form-label>
                                <select name="channel" id="channel" class="form-select" required>
                                    <option value="">Select Channel</option>
                                    @foreach ($channels as $ch)
                                        <option value="{{ $ch }}" {{ old('channel') === $ch ? 'selected' : '' }}>{{ $ch }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('channel')" class="mt-2" />
                            </x-form-group>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="area">Area</x-form-label>
                                <select name="area" id="area" class="form-select">
                                    <option value="">Select Area</option>
                                    @foreach ($dhakaZones as $zone)
                                        <option value="{{ $zone }}" {{ old('area') === $zone ? 'selected' : '' }}>{{ $zone }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="live_in_pref">Live-in Preference</x-form-label>
                                <select name="live_in_pref" id="live_in_pref" class="form-select">
                                    <option value="">Not Specified</option>
                                    @foreach ($liveInPrefs as $pref)
                                        <option value="{{ $pref }}" {{ old('live_in_pref') === $pref ? 'selected' : '' }}>{{ ucfirst($pref) }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="service_interest">Service Interest</x-form-label>
                                <input type="text" name="service_interest" class="form-control" value="{{ old('service_interest') }}" maxlength="500">
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="preferred_start_date">Preferred Start Date</x-form-label>
                                <input type="date" name="preferred_start_date" class="form-control" value="{{ old('preferred_start_date') }}">
                            </x-form-group>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="budget_expectation">Budget (BDT)</x-form-label>
                                <input type="number" name="budget_expectation" class="form-control" value="{{ old('budget_expectation') }}" min="0" step="0.01">
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="owner_id">Assign Coordinator</x-form-label>
                                <select name="owner_id" class="form-select">
                                    <option value="">Unassigned</option>
                                    @foreach ($coordinators as $c)
                                        <option value="{{ $c->id }}" {{ old('owner_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                    </div>
                    <x-form-group>
                        <x-form-label for="notes">Notes</x-form-label>
                        <textarea name="notes" class="form-control" rows="3" maxlength="2000" placeholder="Do not include ID numbers or health details.">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </x-form-group>
                    @if (session('duplicate_warning'))
                    <x-form-group>
                        <x-form-label for="override_reason" required="true">Override Reason</x-form-label>
                        <input type="text" name="override_reason" class="form-control" value="{{ old('override_reason') }}" maxlength="500" required>
                        <x-input-error :messages="$errors->get('override_reason')" class="mt-2" />
                    </x-form-group>
                    @endif
                    <div class="mt-4 border-top pt-5">
                        <button type="submit" class="btn btn-primary">Save Lead</button>
                        <a href="{{ route('leads.index') }}" class="btn btn-light ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
