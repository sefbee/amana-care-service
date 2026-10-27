@extends('layouts.base')
@section('title', 'Edit Caregiver Candidate')
@php($breadcrumb = [['title' => 'Caregiver Recruitment', 'url' => route('caregivers.index')], ['title' => $caregiver->name, 'url' => route('caregivers.show', $caregiver)], ['title' => 'Edit Profile']])
@section('content')
<div class="row"><div class="col-md-8 offset-md-2">
    <x-alert :show_validations="false" />
    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('caregivers.update', $caregiver) }}">@csrf @method('PUT')
            <div class="row">
                <div class="col-md-6"><x-form-group><x-form-label for="name" required="true">Full name</x-form-label><input class="form-control" id="name" name="name" value="{{ old('name', $caregiver->name) }}" required maxlength="150"></x-form-group></div>
                <div class="col-md-6"><x-form-group><x-form-label for="phone" required="true">Phone</x-form-label><input class="form-control" id="phone" name="phone" value="{{ old('phone', $caregiver->phone) }}" required maxlength="20"></x-form-group></div>
                <div class="col-md-6"><x-form-group><x-form-label for="dob" required="true">Date of birth</x-form-label><input class="form-control" type="date" id="dob" name="dob" value="{{ old('dob', $caregiver->dob?->format('Y-m-d')) }}" required max="{{ now()->subDay()->toDateString() }}"></x-form-group></div>
                <div class="col-md-6"><x-form-group><x-form-label for="gender">Gender for matching</x-form-label><select class="form-select" id="gender" name="gender"><option value="">Not specified</option>@foreach(['Female', 'Male', 'Other'] as $option)<option value="{{ $option }}" @selected(old('gender', $caregiver->gender) === $option)>{{ $option }}</option>@endforeach</select></x-form-group></div>
                <div class="col-md-12"><small class="text-muted">National ID is protected and cannot be viewed or changed here. Use the approved duplicate review process if correction is required.</small></div>
                <div class="col-md-6"><x-form-group><x-form-label for="available_from">Available from</x-form-label><input class="form-control" type="date" id="available_from" name="available_from" value="{{ old('available_from', $caregiver->available_from?->format('Y-m-d')) }}"></x-form-group></div>
                <div class="col-md-6 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="live_in_preferred" value="1" id="live_in_preferred" @checked(old('live_in_preferred', $caregiver->live_in_preferred))><label class="form-check-label" for="live_in_preferred">Prefers live-in assignments</label></div></div>
                <div class="col-md-12"><x-form-group><x-form-label for="zone_preferences">Preferred zones</x-form-label><select class="form-select" id="zone_preferences" name="zone_preferences[]" multiple>@foreach(config('crm.dhaka_zones', []) as $zone)<option value="{{ $zone }}" @selected(in_array($zone, old('zone_preferences', $caregiver->zone_preferences ?? [])))>{{ $zone }}</option>@endforeach</select></x-form-group></div>
                <div class="col-md-12"><h6 class="fw-bold mt-3">Skills</h6><div class="row">@foreach($skills as $key => $label)<div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="skills[{{ $key }}]" value="1" @checked(old('skills.'.$key, $caregiver->skills->firstWhere('skill_key', $key)?->is_proficient ?? false))><span class="form-check-label">{{ $label }}</span></label></div>@endforeach</div></div>
            </div>
            <div class="mt-4 border-top pt-4"><button class="btn btn-primary" type="submit">Save profile</button> <a class="btn btn-light" href="{{ route('caregivers.show', $caregiver) }}">Cancel</a></div>
        </form>
    </div></div>
</div></div>
@endsection
