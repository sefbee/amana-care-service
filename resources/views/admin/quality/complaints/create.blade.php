@extends('layouts.base')
@section('title', 'Log Routine Complaint')
@php($breadcrumb = [['title' => 'Quality & Support'], ['title' => 'Routine Complaints', 'url' => route('routine-complaints.index')], ['title' => 'Log complaint']])

@section('content')
    <div class="row"><div class="col-md-8 offset-md-2">
        <x-alert :show_validations="false" />
        <form method="POST" action="{{ route('routine-complaints.store') }}" class="card shadow-sm">
            @csrf
            <div class="card-body">
                <p class="text-muted">Use this form for routine service concerns. Serious allegations, injury, theft, or legal escalation must be reported separately through the restricted serious-incident workflow.</p>
                <x-form-group>
                    <x-form-label for="household_id" required="true">Household</x-form-label>
                    <select class="form-select" id="household_id" name="household_id" required>
                        <option value="">Select household</option>
                        @foreach ($households as $household)<option value="{{ $household->id }}" @selected(old('household_id') == $household->id)>{{ $household->display_name }}</option>@endforeach
                    </select>
                </x-form-group>
                <x-form-group>
                    <x-form-label for="placement_id">Related placement (optional)</x-form-label>
                    <select class="form-select" id="placement_id" name="placement_id">
                        <option value="">No specific placement</option>
                        @foreach ($placements as $placement)<option value="{{ $placement->id }}" data-household="{{ $placement->household_id }}" @selected(old('placement_id') == $placement->id)>#{{ $placement->id }} · {{ $placement->household->display_name }} · {{ $placement->status }}</option>@endforeach
                    </select>
                </x-form-group>
                <x-form-group>
                    <x-form-label for="assigned_to" required="true">QS owner</x-form-label>
                    <select class="form-select" id="assigned_to" name="assigned_to" required>
                        <option value="">Assign to QS user</option>
                        @foreach ($assignees as $assignee)<option value="{{ $assignee->id }}" @selected(old('assigned_to', $currentRoleSlug === 'qs' ? auth()->id() : null) == $assignee->id)>{{ $assignee->name }}</option>@endforeach
                    </select>
                </x-form-group>
                <x-form-group>
                    <x-form-label for="subject" required="true">Subject</x-form-label>
                    <input class="form-control" id="subject" name="subject" maxlength="150" required value="{{ old('subject') }}">
                </x-form-group>
                <x-form-group>
                    <x-form-label for="details" required="true">Complaint details</x-form-label>
                    <textarea class="form-control" id="details" name="details" rows="6" maxlength="10000" required>{{ old('details') }}</textarea>
                    <div class="form-text">Complaint notes are encrypted in the database and visible to QS and the Platform Superuser.</div>
                </x-form-group>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2"><a href="{{ route('routine-complaints.index') }}" class="btn btn-light">Cancel</a><button class="btn btn-primary">Log complaint</button></div>
        </form>
    </div></div>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    const householdSelect = document.getElementById('household_id');
    const placementSelect = document.getElementById('placement_id');
    function filterComplaintPlacements() {
        Array.from(placementSelect.options).forEach(function (option) {
            if (!option.dataset.household) return;
            option.hidden = option.dataset.household !== householdSelect.value;
            if (option.hidden && option.selected) placementSelect.value = '';
        });
    }
    householdSelect.addEventListener('change', filterComplaintPlacements);
    filterComplaintPlacements();
</script>
@endpush
