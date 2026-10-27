<form method="POST" action="{{ route('training-records.store', $caregiver) }}" class="d-flex gap-2 align-items-center">
    @csrf
    <input type="hidden" name="training_module_id" value="{{ $module->id }}">
    <select name="outcome" class="form-select form-select-sm" aria-label="Training outcome" required>
        @foreach (['Pending', 'Pass', 'Fail'] as $outcome)
            <option value="{{ $outcome }}" @selected($record?->outcome === $outcome)>{{ $outcome }}</option>
        @endforeach
    </select>
    <input type="date" name="attended_at" value="{{ $record?->attended_at?->format('Y-m-d') }}" class="form-control form-control-sm" aria-label="Attendance date" max="{{ now()->toDateString() }}" required>
    <button type="submit" class="btn btn-sm btn-primary">Save</button>
</form>
