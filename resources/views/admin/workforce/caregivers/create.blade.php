@extends('layouts.base')
@section('title', 'Add Caregiver Candidate')
@php($breadcrumb = [['title' => 'Caregiver Recruitment', 'url' => route('caregivers.index')], ['title' => 'Add Candidate']])
@section('content')
<div class="row"><div class="col-md-8 offset-md-2">
    <x-alert :show_validations="false" />
    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('caregivers.store') }}">@csrf
            <div class="row">
                <div class="col-md-6"><x-form-group><x-form-label for="name" required="true">Full name</x-form-label><input class="form-control" id="name" name="name" value="{{ old('name') }}" required maxlength="150"></x-form-group></div>
                <div class="col-md-6"><x-form-group><x-form-label for="phone" required="true">Phone</x-form-label><input class="form-control" id="phone" name="phone" value="{{ old('phone') }}" required maxlength="20"></x-form-group></div>
                <div class="col-md-6"><x-form-group><x-form-label for="dob" required="true">Date of birth</x-form-label><input class="form-control" type="date" id="dob" name="dob" value="{{ old('dob') }}" required max="{{ now()->subDay()->toDateString() }}"></x-form-group></div>
                <div class="col-md-6"><x-form-group><x-form-label for="gender">Gender for matching</x-form-label><select class="form-select" id="gender" name="gender"><option value="">Not specified</option>@foreach(['Female', 'Male', 'Other'] as $option)<option value="{{ $option }}" @selected(old('gender') === $option)>{{ $option }}</option>@endforeach</select></x-form-group></div>
                <div class="col-md-12"><x-form-group><x-form-label for="national_id" required="true">National ID</x-form-label><input class="form-control" type="password" autocomplete="new-password" id="national_id" name="national_id" required maxlength="100"><small class="text-muted">Used only to create a protected duplicate check. The entered value is not retained or displayed.</small></x-form-group></div>
                <div class="col-md-6"><x-form-group><x-form-label for="available_from">Available from</x-form-label><input class="form-control" type="date" id="available_from" name="available_from" value="{{ old('available_from') }}"></x-form-group></div>
                <div class="col-md-6 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="live_in_preferred" value="1" id="live_in_preferred" @checked(old('live_in_preferred'))><label class="form-check-label" for="live_in_preferred">Prefers live-in assignments</label></div></div>
                <div class="col-md-12"><x-form-group><x-form-label for="zone_preferences">Preferred zones</x-form-label><select class="form-select" id="zone_preferences" name="zone_preferences[]" multiple>@foreach(config('crm.dhaka_zones', []) as $zone)<option value="{{ $zone }}" @selected(in_array($zone, old('zone_preferences', [])))>{{ $zone }}</option>@endforeach</select></x-form-group></div>
                <div class="col-md-12"><h6 class="fw-bold mt-3">Skills</h6><div class="row">@foreach($skills as $key => $label)<div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="skills[{{ $key }}]" value="1" @checked(old('skills.'.$key))><span class="form-check-label">{{ $label }}</span></label></div>@endforeach</div></div>
            </div>
            <div class="mt-4 border-top pt-4"><button class="btn btn-primary" type="submit">Save candidate</button> <a class="btn btn-light" href="{{ route('caregivers.index') }}">Cancel</a></div>
        </form>
    </div></div>
</div></div>
@endsection
