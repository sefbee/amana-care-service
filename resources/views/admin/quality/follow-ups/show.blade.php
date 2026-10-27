@extends('layouts.base')
@section('title', 'Day '.$task->milestone_day.' Follow-up')
@php($breadcrumb = [['title' => 'Quality & Support'], ['title' => 'Placement Follow-ups', 'url' => route('placement-follow-ups.index')], ['title' => 'Day '.$task->milestone_day]])
@section('actions')
    <a href="{{ route('placement-follow-ups.index') }}" class="btn btn-sm btn-light-primary">Back to follow-ups</a>
@endsection
@section('content')
<div class="row"><div class="col-md-8 offset-md-2">
    <x-alert :show_validations="false" />
    <div class="card shadow-sm mb-4"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Placement details</h6></div><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Household</dt><dd class="col-sm-8">{{ $task->placement->household->display_name ?? 'Household' }}</dd>
            <dt class="col-sm-4">Caregiver</dt><dd class="col-sm-8">{{ $task->placement->caregiver->name ?? 'Caregiver' }}</dd>
            <dt class="col-sm-4">Milestone</dt><dd class="col-sm-8">Day {{ $task->milestone_day }}</dd>
            <dt class="col-sm-4">Due</dt><dd class="col-sm-8">{{ $task->due_at->format('d M Y') }}</dd>
            <dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ $task->status }}</dd>
        </dl>
    </div></div>

    @if ($task->status === 'Open' && auth()->user()->hasPermission('PlacementFollowUpController@recordAttempt'))
        <div class="card shadow-sm mb-4"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Record contact attempt</h6></div><div class="card-body">
            <form method="POST" action="{{ route('placement-follow-ups.attempts.store', $task) }}">
                @csrf
                <div class="mb-3"><label class="form-label" for="outcome">Outcome</label><select class="form-select" id="outcome" name="outcome" required>
                    <option value="">Choose an outcome</option><option value="Satisfied" @selected(old('outcome') === 'Satisfied')>Satisfied</option><option value="Concern" @selected(old('outcome') === 'Concern')>Concern</option><option value="Escalate" @selected(old('outcome') === 'Escalate')>Escalate</option>
                </select></div>
                <div class="mb-3"><label class="form-label" for="notes">Contact notes</label><textarea class="form-control" id="notes" name="notes" rows="5" maxlength="4000" required>{{ old('notes') }}</textarea></div>
                <div class="d-flex gap-2"><button class="btn btn-primary" type="submit">Save outcome</button><a class="btn btn-light" href="{{ route('placement-follow-ups.index') }}">Cancel</a></div>
            </form>
        </div></div>
    @endif

    @if ($task->attempts->isNotEmpty())
        <div class="card shadow-sm"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Recorded outcome</h6></div><div class="card-body">
            @foreach ($task->attempts as $attempt)
                <dl class="row mb-0">
                    <dt class="col-sm-4">Outcome</dt><dd class="col-sm-8">{{ $attempt->outcome }}</dd>
                    <dt class="col-sm-4">Recorded by</dt><dd class="col-sm-8">{{ $attempt->actor?->name ?? 'Former user' }}</dd>
                    <dt class="col-sm-4">Recorded at</dt><dd class="col-sm-8">{{ $attempt->attempted_at->format('d M Y, h:i A') }}</dd>
                    <dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $attempt->notes }}</dd>
                </dl>
            @endforeach
        </div></div>
    @endif
</div></div>
@endsection
