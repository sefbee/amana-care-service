@extends('layouts.base')

@section('title', 'Add Conflict-of-Interest Block')

@php($breadcrumb = [
    ['title' => 'CRM', 'url' => route('leads.index')],
    ['title' => 'Conflict-of-Interest Blocks', 'url' => route('record-conflict-blocks.index')],
    ['title' => 'Add Block'],
])

@section('content')
<div class="row"><div class="col-md-8 offset-md-2">
    <x-alert :show_validations="false" />
    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('record-conflict-blocks.store') }}">
            @csrf
            <x-form-group>
                <x-form-label for="staff_user_id" required="true">Staff member</x-form-label>
                <select class="form-select" id="staff_user_id" name="staff_user_id" required>
                    <option value="">Select staff member</option>
                    @foreach($staff as $person)
                        <option value="{{ $person->id }}" @selected(old('staff_user_id') == $person->id)>{{ $person->name }}{{ $person->userid ? ' ('.$person->userid.')' : '' }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('staff_user_id')" class="mt-2" />
            </x-form-group>
            <x-form-group>
                <x-form-label for="target_type" required="true">Record type</x-form-label>
                <select class="form-select" id="target_type" name="target_type" required>
                    <option value="">Select record type</option>
                    <option value="household" @selected(old('target_type') === 'household')>Household</option>
                    <option value="caregiver" @selected(old('target_type') === 'caregiver')>Caregiver</option>
                </select>
                <x-input-error :messages="$errors->get('target_type')" class="mt-2" />
            </x-form-group>
            <div class="household-target" @if(old('target_type') !== 'household') hidden @endif>
                <x-form-group>
                    <x-form-label for="household_id" required="true">Household</x-form-label>
                <select class="form-select" id="household_id" name="household_id" @disabled(old('target_type') !== 'household')>
                        <option value="">Select household</option>
                        @foreach($households as $household)
                            <option value="{{ $household->id }}" @selected(old('household_id') == $household->id)>{{ $household->display_name }}{{ $household->area ? ' — '.$household->area : '' }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('household_id')" class="mt-2" />
                </x-form-group>
            </div>
            <div class="caregiver-target" @if(old('target_type') !== 'caregiver') hidden @endif>
                <x-form-group>
                    <x-form-label for="caregiver_id" required="true">Caregiver</x-form-label>
                <select class="form-select" id="caregiver_id" name="caregiver_id" @disabled(old('target_type') !== 'caregiver')>
                        <option value="">Select caregiver</option>
                        @foreach($caregivers as $caregiver)
                            <option value="{{ $caregiver->id }}" @selected(old('caregiver_id') == $caregiver->id)>{{ $caregiver->name }}{{ $caregiver->phone ? ' — '.$caregiver->phone : '' }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('caregiver_id')" class="mt-2" />
                </x-form-group>
            </div>
            <x-form-group>
                <x-form-label for="reason" required="true">Reason for block</x-form-label>
                <textarea class="form-control" id="reason" name="reason" rows="3" minlength="10" maxlength="500" required>{{ old('reason') }}</textarea>
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
            </x-form-group>
            <div class="d-flex gap-2 mt-4 border-top pt-4">
                <button type="submit" class="btn btn-primary">Apply Block</button>
                <a href="{{ route('record-conflict-blocks.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div></div>
</div></div>
@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    const targetType = document.getElementById('target_type');
    const syncTargets = () => {
        const household = targetType.value === 'household';
        const caregiver = targetType.value === 'caregiver';
        document.querySelector('.household-target').hidden = !household;
        document.querySelector('.caregiver-target').hidden = !caregiver;
        document.getElementById('household_id').required = household;
        document.getElementById('household_id').disabled = !household;
        document.getElementById('caregiver_id').required = caregiver;
        document.getElementById('caregiver_id').disabled = !caregiver;
    };
    targetType?.addEventListener('change', syncTargets);
    syncTargets();
</script>
@endpush
@endsection
