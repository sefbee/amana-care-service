@extends('layouts.base')
@section('title', 'Preliminary Candidate Review')
@php($breadcrumb = [
    ['title' => 'CRM'],
    ['title' => 'Households', 'url' => route('households.index')],
    ['title' => $serviceRequest->household->display_name, 'url' => route('households.show', $serviceRequest->household)],
    ['title' => $serviceRequest->service->name.' candidates'],
])
@section('actions')
    <a href="{{ route('households.show', $serviceRequest->household) }}" class="btn btn-sm btn-light">Back to household</a>
@endsection
@section('content')
<div class="row"><div class="col-md-12">
    <div class="alert alert-warning" role="status">
        These are preliminary readiness, configured-skill and care-arrangement filters. Schedule-overlap checks are still missing. You may save an internal proposal draft for review, but these candidates cannot be sent to a family or activated as placements.
    </div>
    <x-alert :show_validations="false" />
    @if($requiredSkills === [])
        <div class="alert alert-danger">No service skill requirements are configured. Ask an Operations Manager to set the required caregiver skills on this service before using matching.</div>
    @endif
    <div class="card mb-4"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Eligible preliminary candidates ({{ $included->count() }})</h6></div><div class="card-body">
        @forelse($included as $candidate)
            <div class="border-bottom py-3"><div class="d-flex justify-content-between"><strong>{{ $candidate['name'] }}</strong><label class="form-check"><input class="form-check-input proposal-candidate" form="proposal-draft-form" type="checkbox" name="candidate_ids[]" value="{{ $candidate['id'] }}"><span class="form-check-label">Include in draft</span></label><span class="badge badge-light-success">Ready</span></div>
                <div class="small text-muted">Available from: {{ $candidate['available_from']?->format('d M Y') ?? 'Not recorded' }} · {{ $candidate['zone_match'] ? 'Preferred zone match' : 'Zone preference not matched' }}</div>
                <div class="mt-2">@foreach($candidate['skills'] as $skill)<span class="badge badge-light-primary me-1">{{ $skill }}</span>@endforeach</div>
            </div>
        @empty
            <p class="text-muted mb-0">No candidates currently meet the preliminary filters.</p>
        @endforelse
    </div></div>

    @if($requiredSkills !== [] && $included->isNotEmpty() && auth()->user()->hasPermission('ProposalController@store'))
    <div class="card mb-4"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Save internal proposal draft</h6></div><div class="card-body">
        <form id="proposal-draft-form" method="POST" action="{{ route('service-requests.proposals.store', $serviceRequest) }}">@csrf
            <label for="rationale" class="form-label">Matching rationale (at least 20 characters)</label>
            <textarea class="form-control" id="rationale" name="rationale" minlength="20" maxlength="2000" rows="3" required>{{ old('rationale') }}</textarea>
            <button class="btn btn-primary mt-3" type="submit">Save draft proposal</button>
            <small class="d-block text-muted mt-2">Select 1–3 candidates. Saving records a human selection; it does not notify the family or assign a caregiver.</small>
        </form>
    </div></div>
    @endif

    <div class="card"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Excluded candidates and reasons ({{ $excluded->count() }})</h6></div><div class="card-body">
        @forelse($excluded as $candidate)
            <div class="border-bottom py-3"><strong>{{ $candidate['name'] }}</strong>
                <ul class="small text-danger mb-0">@foreach($candidate['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
            </div>
        @empty
            <p class="text-muted mb-0">No candidates were excluded by the current filters.</p>
        @endforelse
    </div></div>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', function () {
        const boxes = Array.from(document.querySelectorAll('.proposal-candidate'));
        boxes.forEach(box => box.addEventListener('change', () => {
            const selected = boxes.filter(item => item.checked).length;
            boxes.forEach(item => { if (!item.checked) item.disabled = selected >= 3; });
        }));
    });
</script>
@endpush
