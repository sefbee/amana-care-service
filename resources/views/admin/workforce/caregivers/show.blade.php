@extends('layouts.base')
@section('title', 'Caregiver Candidate')
@php($breadcrumb = [['title' => 'Caregiver Recruitment', 'url' => route('caregivers.index')], ['title' => $caregiver->name]])
@section('actions')
    @if(auth()->user()->hasPermission('CaregiverController@edit'))
        <a href="{{ route('caregivers.edit', $caregiver) }}" class="btn btn-sm btn-primary">Edit profile</a>
    @endif
    <a href="{{ route('caregivers.index') }}" class="btn btn-sm btn-light">Back to candidates</a>
@endsection
@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Candidate profile</h6></div><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-5">Name</dt><dd class="col-sm-7">{{ $caregiver->name }}</dd>
                <dt class="col-sm-5">Date of birth</dt><dd class="col-sm-7">{{ $caregiver->dob?->format('d M Y') }}</dd>
                <dt class="col-sm-5">Gender for matching</dt><dd class="col-sm-7">{{ $caregiver->gender ?: 'Not specified' }}</dd>
                <dt class="col-sm-5">Phone</dt><dd class="col-sm-7">{{ $caregiver->phone }}</dd>
                <dt class="col-sm-5">Live-in preference</dt><dd class="col-sm-7">{{ $caregiver->live_in_preferred ? 'Preferred' : 'No preference recorded' }}</dd>
                <dt class="col-sm-5">Available from</dt><dd class="col-sm-7">{{ $caregiver->available_from?->format('d M Y') ?? 'Not recorded' }}</dd>
                <dt class="col-sm-5">Zones</dt><dd class="col-sm-7">{{ implode(', ', $caregiver->zone_preferences ?? []) ?: 'Not recorded' }}</dd>
                <dt class="col-sm-5">Recruitment stage</dt><dd class="col-sm-7">{{ $caregiver->recruitment_stage }}</dd>
                <dt class="col-sm-5">Readiness</dt><dd class="col-sm-7"><span class="badge {{ $caregiver->status === 'Ready' ? 'badge-light-success' : 'badge-light-warning' }}">{{ $caregiver->status }}</span></dd>
            </dl>
            <hr><h6 class="fw-bold">Recorded skills</h6>
            @forelse($caregiver->skills->where('is_proficient', true) as $skill)<span class="badge badge-light-primary me-1 mb-1">{{ config('caregivers.skills.'.$skill->skill_key, $skill->skill_key) }}</span>@empty<p class="text-muted mb-0">No skills recorded.</p>@endforelse
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card mb-4"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Readiness gates</h6></div><div class="card-body">
            <p class="small text-muted">Each final identity, police and psych result requires screening approval by Operations or Safeguarding. Readiness also requires mandatory training, no safeguarding hold, and a separate Operations Manager readiness decision.</p>
            @foreach(['Identity', 'Police', 'Psych'] as $type)
                @php($check = $caregiver->screeningChecks->where('type', $type)->sortByDesc('checked_at')->first())
                @php($verificationStatus = $check?->verification_status === 'Needs Verification' ? ' · Needs Verification' : '')
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $type }} check</span><span>{{ $check ? $check->outcome.$verificationStatus.' · '.($check->checked_at?->format('d M Y') ?? '').($check->approved_at ? ' · Approved by '.($check->approver?->name ?? 'Screening approver') : ' · Approval required') : 'Not recorded' }}</span></div>
                @if($check && $check->verification_status === 'Verified' && $check->outcome !== 'Pending' && ! $check->approved_at && auth()->user()->hasPermission('CaregiverController@approveScreeningCheck'))
                    <form class="border-bottom py-2" method="POST" action="{{ route('caregivers.checks.approve', [$caregiver, $check]) }}">@csrf
                        <label class="form-label" for="screening-approval-reason-{{ $check->id }}">Reason for approving this result</label>
                        <div class="input-group"><input id="screening-approval-reason-{{ $check->id }}" class="form-control" name="approval_reason" maxlength="500" required><button class="btn btn-sm btn-light-primary" type="submit">Approve result</button></div>
                    </form>
                @elseif($check?->approval_reason)
                    <p class="small text-muted border-bottom pb-2">Screening approval reason: {{ $check->approval_reason }}</p>
                @endif
            @endforeach
            @if(auth()->user()->hasPermission('CaregiverController@recordCheck'))
            <form class="border-top mt-3 pt-3" method="POST" action="{{ route('caregivers.checks.store', $caregiver) }}">@csrf
                <div class="row g-3"><div class="col-md-3"><label class="form-label">Check type</label><select class="form-select" name="type" required><option>Identity</option><option>Police</option><option>Psych</option></select></div>
                    <div class="col-md-3"><label class="form-label">Outcome</label><select class="form-select" name="outcome" required><option>Pending</option><option>Pass</option><option>Fail</option><option>Adverse</option></select></div>
                    <div class="col-md-3"><label class="form-label">Checked at</label><input class="form-control" type="date" name="checked_at" max="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-3"><label class="form-label">Valid until (Police required)</label><input class="form-control" type="date" name="valid_until" min="{{ now()->toDateString() }}"></div>
                    <div class="col-md-8"><label class="form-label">Clean evidence document</label><select class="form-select" name="evidence_document_id" required><option value="">Select clean evidence</option>@foreach($caregiver->documents->where('scan_status', 'Clean') as $document)<option value="{{ $document->id }}">{{ $document->type }} · {{ $document->original_name }}</option>@endforeach</select></div>
                    <div class="col-md-4 d-flex align-items-end"><button class="btn btn-sm btn-primary">Record check</button></div></div>
            </form>
            @endif
            @foreach($trainingModules as $module)
                @php($record = $caregiver->trainingRecords->firstWhere('training_module_id', $module->id))
                @if($module->is_mandatory)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $module->title }} <small class="text-muted">(mandatory)</small></span><span>{{ $record?->outcome ?? 'Incomplete' }}</span></div>@endif
            @endforeach
            <div class="d-flex justify-content-between border-bottom py-2"><span>Safeguarding hold</span><span>{{ $caregiver->safeguarding_hold_at ? 'Active' : 'None recorded' }}</span></div>
            @if($caregiver->readinessApprovals->isNotEmpty())<p class="mt-3 mb-0">Latest OM decision: <strong>{{ $caregiver->readinessApprovals->first()->approved ? 'Approved' : 'Not approved' }}</strong> — {{ $caregiver->readinessApprovals->first()->reason }}</p>@endif
        </div></div>

        @if(auth()->user()->hasPermission('CaregiverController@approveReadiness'))
        <div class="card"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Operations Manager readiness decision</h6></div><div class="card-body">
            <form method="POST" action="{{ route('caregivers.readiness.store', $caregiver) }}">@csrf
                <div class="row"><div class="col-md-4"><label class="form-label">Decision</label><select name="approved" class="form-select" required><option value="1">Approve</option><option value="0">Do not approve</option></select></div><div class="col-md-8"><label class="form-label">Reason</label><input class="form-control" name="reason" maxlength="500" required></div></div>
                <button class="btn btn-sm btn-primary mt-3">Record decision</button>
            </form>
        </div></div>
        @endif

        <div class="card mt-4"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Vetting documents</h6></div><div class="card-body">
            @forelse($caregiver->documents as $document)
                <div class="d-flex align-items-center justify-content-between border-bottom py-2"><span>{{ $document->type }} · {{ $document->original_name }} <span class="badge badge-light-secondary">{{ $document->scan_status }}</span></span>
                    @if(auth()->user()->hasPermission('CaregiverController@revealDocument'))
                    <form method="POST" action="{{ route('caregivers.documents.reveal', $document) }}">@csrf
                        <select name="reason_code" class="form-select form-select-sm d-inline-block w-auto" required>@foreach(config('caregivers.reveal_reasons', []) as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select>
                        <button class="btn btn-sm btn-light-primary" type="submit" @disabled($document->scan_status !== 'Clean')>Reveal · 5 min</button>
                    </form>
                    @endif
                </div>
            @empty
                <p class="text-muted">No vetting documents recorded.</p>
            @endforelse
            @if(auth()->user()->hasPermission('CaregiverController@storeDocument'))
                <form class="border-top mt-3 pt-3" method="POST" enctype="multipart/form-data" action="{{ route('caregivers.documents.store', $caregiver) }}">@csrf
                    <div class="row g-3"><div class="col-md-3"><label class="form-label">Type</label><select class="form-select" name="type" required><option>Identity</option><option>Police</option><option>Psych</option></select></div>
                        <div class="col-md-5"><label class="form-label">PDF, JPEG or PNG (max 10 MB)</label><input class="form-control" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></div>
                        <div class="col-md-3"><label class="form-label">Document expiry</label><input class="form-control" type="date" name="expiry_date" min="{{ now()->toDateString() }}"></div>
                        <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary" type="submit">Upload</button></div></div>
                    <small class="text-muted">Uploads go to a private quarantine bucket and remain blocked unless malware scanning succeeds.</small>
                </form>
            @endif
        </div></div>
    </div>
</div>
@endsection
