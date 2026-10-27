@extends('layouts.base')
@section('title', 'New Household')

@php
    $breadcrumb = [
        ['title' => 'CRM'],
        ['title' => 'Households', 'url' => route('households.index')],
        ['title' => 'New Household'],
    ];
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <x-alert :show_validations="false" />
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('households.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="display_name" required="true">Household Name</x-form-label>
                                <input type="text" name="display_name" class="form-control" value="{{ old('display_name') }}" maxlength="100" required>
                                <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="area" required="true">Area</x-form-label>
                                <select name="area" class="form-select" required>
                                    <option value="">Select Area</option>
                                    @foreach ($dhakaZones as $zone)
                                        <option value="{{ $zone }}" {{ old('area') === $zone ? 'selected' : '' }}>{{ $zone }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('area')" class="mt-2" />
                            </x-form-group>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="primary_language" required="true">Primary Language</x-form-label>
                                <select name="primary_language" class="form-select" required>
                                    @foreach ($languages as $lang)
                                        <option value="{{ $lang }}" {{ old('primary_language') === $lang ? 'selected' : '' }}>{{ $lang }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="assigned_coordinator_id" required="true">Assigned Coordinator</x-form-label>
                                <select name="assigned_coordinator_id" class="form-select" required>
                                    <option value="">Select Coordinator</option>
                                    @foreach ($coordinators as $c)
                                        <option value="{{ $c->id }}" {{ old('assigned_coordinator_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('assigned_coordinator_id')" class="mt-2" />
                            </x-form-group>
                        </div>
                    </div>
                    <div class="border-top pt-4 mt-2">
                        <h6 class="card-title mb-3 fw-bold">Household Contacts</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="primary_contact_name" required="true">Primary Decision Maker Name</x-form-label>
                                    <input type="text" name="primary_contact_name" class="form-control" value="{{ old('primary_contact_name') }}" maxlength="150" required>
                                    <x-input-error :messages="$errors->get('primary_contact_name')" />
                                </x-form-group>
                            </div>
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="primary_contact_phone" required="true">Primary Contact Phone</x-form-label>
                                    <input type="text" name="primary_contact_phone" class="form-control" value="{{ old('primary_contact_phone') }}" required>
                                    <x-input-error :messages="$errors->get('primary_contact_phone')" />
                                </x-form-group>
                            </div>
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="primary_contact_email">Primary Contact Email</x-form-label>
                                    <input type="email" name="primary_contact_email" class="form-control" value="{{ old('primary_contact_email') }}" maxlength="254">
                                </x-form-group>
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="billing_same_as_primary" value="0">
                                    <input class="form-check-input" type="checkbox" name="billing_same_as_primary" id="billing_same_as_primary" value="1" {{ old('billing_same_as_primary', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="billing_same_as_primary">Billing Contact is the same person</label>
                                </div>
                                <x-input-error :messages="$errors->get('billing_same_as_primary')" />
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4">
                                <x-form-group>
                                    <x-form-label for="billing_contact_name">Billing Contact Name</x-form-label>
                                    <input type="text" name="billing_contact_name" class="form-control" value="{{ old('billing_contact_name') }}" maxlength="150">
                                    <x-input-error :messages="$errors->get('billing_contact_name')" />
                                </x-form-group>
                            </div>
                            <div class="col-md-4">
                                <x-form-group>
                                    <x-form-label for="billing_contact_phone">Billing Contact Phone</x-form-label>
                                    <input type="text" name="billing_contact_phone" class="form-control" value="{{ old('billing_contact_phone') }}">
                                    <x-input-error :messages="$errors->get('billing_contact_phone')" />
                                </x-form-group>
                            </div>
                            <div class="col-md-4">
                                <x-form-group>
                                    <x-form-label for="billing_contact_email">Billing Contact Email</x-form-label>
                                    <input type="email" name="billing_contact_email" class="form-control" value="{{ old('billing_contact_email') }}" maxlength="254">
                                    <x-input-error :messages="$errors->get('billing_contact_email')" />
                                </x-form-group>
                            </div>
                        </div>
                    </div>
                    @if ($canViewSensitiveData)
                        <x-form-group>
                            <x-form-label for="address_line">Address</x-form-label>
                            <textarea name="address_line" class="form-control" rows="2" maxlength="300">{{ old('address_line') }}</textarea>
                            <small class="text-muted">Sensitive — not shown in lists.</small>
                        </x-form-group>
                    @endif
                    <x-form-group>
                        <x-form-label for="access_notes">Access Notes</x-form-label>
                        <input type="text" name="access_notes" class="form-control" value="{{ old('access_notes') }}" maxlength="500">
                        <x-input-error :messages="$errors->get('access_notes')" class="mt-2" />
                    </x-form-group>
                    <x-form-group>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="founding_cohort" id="founding_cohort" value="1" {{ old('founding_cohort') ? 'checked' : '' }}>
                            <label class="form-check-label" for="founding_cohort">Founding Cohort Member</label>
                        </div>
                    </x-form-group>
                    <div class="mt-4 border-top pt-5">
                        <button type="submit" class="btn btn-primary">Save Household</button>
                        <a href="{{ route('households.index') }}" class="btn btn-light ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
