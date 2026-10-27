@extends('layouts.base')
@section('title', 'Edit Household')

@php
    $breadcrumb = [
        ['title' => 'CRM'],
        ['title' => 'Households', 'url' => route('households.index')],
        ['title' => $household->display_name, 'url' => route('households.show', $household->id)],
        ['title' => 'Edit'],
    ];
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <x-alert :show_validations="false" />
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('households.update', $household->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="display_name" required="true">Household Name</x-form-label>
                                <input type="text" name="display_name" class="form-control" value="{{ old('display_name', $household->display_name) }}" maxlength="100" required>
                                <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                            </x-form-group>
                        </div>
                        <div class="col-md-6">
                            <x-form-group>
                                <x-form-label for="area" required="true">Area</x-form-label>
                                <select name="area" class="form-select" required>
                                    @foreach ($dhakaZones as $zone)
                                        <option value="{{ $zone }}" {{ old('area', $household->area) === $zone ? 'selected' : '' }}>{{ $zone }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <x-form-group>
                                <x-form-label for="primary_language" required="true">Language</x-form-label>
                                <select name="primary_language" class="form-select" required>
                                    @foreach ($languages as $lang)
                                        <option value="{{ $lang }}" {{ old('primary_language', $household->primary_language) === $lang ? 'selected' : '' }}>{{ $lang }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                        <div class="col-md-4">
                            <x-form-group>
                                <x-form-label for="status" required="true">Status</x-form-label>
                                <select name="status" class="form-select" required>
                                    @foreach ($statuses as $s)
                                        <option value="{{ $s }}" {{ old('status', $household->status) === $s ? 'selected' : '' }}>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                        <div class="col-md-4">
                            <x-form-group>
                                <x-form-label for="assigned_coordinator_id" required="true">Coordinator</x-form-label>
                                <select name="assigned_coordinator_id" class="form-select" required>
                                    @foreach ($coordinators as $c)
                                        <option value="{{ $c->id }}" {{ old('assigned_coordinator_id', $household->assigned_coordinator_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                    </div>
                    @if ($canViewSensitiveData)
                        <x-form-group>
                            <x-form-label for="address_line">Address</x-form-label>
                            <textarea name="address_line" class="form-control" rows="2" maxlength="300">{{ old('address_line', $household->getRawOriginal('address_line')) }}</textarea>
                            <small class="text-muted">Sensitive household data.</small>
                        </x-form-group>
                    @endif
                    <x-form-group>
                        <x-form-label for="access_notes">Access Notes</x-form-label>
                        <input type="text" name="access_notes" class="form-control" value="{{ old('access_notes', $household->access_notes) }}" maxlength="500">
                        <x-input-error :messages="$errors->get('access_notes')" class="mt-2" />
                    </x-form-group>
                    <x-form-group>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="founding_cohort" id="founding_cohort" value="1" {{ old('founding_cohort', $household->founding_cohort) ? 'checked' : '' }}>
                            <label class="form-check-label" for="founding_cohort">Founding Cohort Member</label>
                        </div>
                    </x-form-group>
                    <div class="mt-4 border-top pt-5">
                        <button type="submit" class="btn btn-primary">Update Household</button>
                        <a href="{{ route('households.show', $household->id) }}" class="btn btn-light ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
