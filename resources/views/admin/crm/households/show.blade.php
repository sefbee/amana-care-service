@extends('layouts.base')
@section('title', 'Household — ' . $household->display_name)

@php
    $breadcrumb = [
        ['title' => 'CRM'],
        ['title' => 'Households', 'url' => route('households.index')],
        ['title' => $household->display_name],
    ];
@endphp

@section('actions')
    <a href="{{ route('households.edit', $household->id) }}" class="btn btn-sm fw-bold btn-primary">Edit</a>
@endsection

@section('tabs')
    <ul class="nav nav-tabs mt-3">
        <li class="nav-item"><a class="nav-link {{ old('service_id') ? '' : 'active' }}" data-bs-toggle="tab" href="#details">Details</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#contacts">Contacts <span class="badge badge-light-primary ms-1">{{ $household->contacts->count() }}</span></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#recipients">Care Recipients <span class="badge badge-light-info ms-1">{{ $household->careRecipients->count() + $restrictedRecipientCount }}</span></a></li>
        <li class="nav-item"><a class="nav-link {{ old('service_id') ? 'active' : '' }}" data-bs-toggle="tab" href="#service-requests">Service Requests <span class="badge badge-light-primary ms-1">{{ $household->serviceRequests->count() }}</span></a></li>
    </ul>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        @if($errors->any())<x-alert :show_validations="false" />@endif
        <div class="tab-content">

            {{-- DETAILS --}}
            <div class="tab-pane fade {{ old('service_id') ? '' : 'show active' }}" id="details">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr><th class="w-40">Area</th><td>{{ $household->area }}</td></tr>
                                    <tr><th>Language</th><td>{{ $household->primary_language }}</td></tr>
                                    <tr><th>Status</th><td>{!! $household->status_badge !!}</td></tr>
                                    <tr><th>Coordinator</th><td>{{ $household->coordinator->name ?? '-' }}</td></tr>
                                    @if ($addressLine !== null)<tr><th>Address</th><td>{{ $addressLine }}</td></tr>@endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr><th class="w-40">Access Notes</th><td>{{ $household->access_notes ?? '-' }}</td></tr>
                                    <tr><th>Founding Cohort</th><td>{{ $household->founding_cohort ? 'Yes' : 'No' }}</td></tr>
                                    <tr><th>Created</th><td>{{ $household->created_at->format('d M Y') }}</td></tr>
                                </table>
                            </div>
                        </div>
                        @if ($household->leads->isNotEmpty())
                        <hr>
                        <h6>Linked Leads</h6>
                        <ul>
                            @foreach ($household->leads as $lead)
                            <li><a href="{{ route('leads.show', $lead->id) }}">{{ $lead->full_name }}</a> — {!! $lead->status_badge !!}</li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- CONTACTS --}}
            <div class="tab-pane fade" id="contacts">
                <div class="card mb-4">
                    <div class="card-header"><h6 class="card-title mb-0 fw-bold">Add Contact</h6></div>
                    <div class="card-body">
                        <form action="{{ route('households.contacts.store', $household->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <x-form-group>
                                        <x-form-label for="contact_name" required="true">Name</x-form-label>
                                        <input type="text" name="name" class="form-control" maxlength="150" required>
                                    </x-form-group>
                                </div>
                                <div class="col-md-3">
                                    <x-form-group>
                                        <span class="form-label d-block">Contact responsibilities</span>
                                        <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_primary_decision_maker" value="1"> Primary</label>
                                        <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_billing_contact" value="1"> Billing</label>
                                        <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_emergency_contact" value="1"> Emergency</label>
                                        <x-input-error :messages="$errors->get('roles')" />
                                    </x-form-group>
                                </div>
                                <div class="col-md-3">
                                    <x-form-group>
                                        <x-form-label for="contact_phone" required="true">Phone</x-form-label>
                                        <input type="text" name="phone" class="form-control" maxlength="20" required>
                                    </x-form-group>
                                </div>
                                <div class="col-md-3">
                                    <x-form-group>
                                        <x-form-label for="contact_email">Email</x-form-label>
                                        <input type="email" name="email" class="form-control" maxlength="254">
                                    </x-form-group>
                                </div>
                                <div class="col-md-1 d-flex align-items-end mb-5">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        @forelse ($household->contacts as $contact)
                        <div class="border-bottom p-3">
                            <form action="{{ route('households.contacts.update', [$household->id, $contact->id]) }}" method="POST" class="row g-2 align-items-center">
                                @csrf @method('PUT')
                                <div class="col-md-3"><input name="name" class="form-control form-control-sm" value="{{ $contact->name }}" maxlength="150" required aria-label="Contact name"></div>
                                <div class="col-md-2"><input name="phone" class="form-control form-control-sm" value="{{ $contact->phone }}" required aria-label="Contact phone"></div>
                                <div class="col-md-2"><input type="email" name="email" class="form-control form-control-sm" value="{{ $contact->email }}" maxlength="254" aria-label="Contact email"></div>
                                <div class="col-md-3">
                                    <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_primary_decision_maker" value="1" @checked($contact->is_primary_decision_maker)> Primary</label>
                                    <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_billing_contact" value="1" @checked($contact->is_billing_contact)> Billing</label>
                                    <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_emergency_contact" value="1" @checked($contact->is_emergency_contact)> Emergency</label>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-light-primary">Save</button>
                                </div>
                            </form>
                            @if (auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('Household\\HouseholdContactController@destroy'))
                                <form action="{{ route('households.contacts.destroy', [$household->id, $contact->id]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-light-danger btn-sm" aria-label="Delete contact" data-confirm="Delete this contact?"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            @endif
                        </div>
                        @empty
                        <div class="p-4 text-center text-muted">No contacts added yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- CARE RECIPIENTS --}}
            <div class="tab-pane fade" id="recipients">
                @if ($restrictedRecipientCount > 0)
                    <div class="alert alert-warning" role="status">Restricted care recipient details are available only to staff with explicit sensitive data access. Contact the Operations Manager if access is required.</div>
                @endif
                <div class="card mb-4">
                    <div class="card-header"><h6 class="card-title mb-0 fw-bold">Add Care Recipient</h6></div>
                    <div class="card-body">
                        <form action="{{ route('households.care-recipients.store', $household->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-3">
                                    <x-form-group>
                                        <x-form-label for="first_name" required="true">First Name</x-form-label>
                                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" maxlength="100" required>
                                        <x-input-error :messages="$errors->get('first_name')" />
                                    </x-form-group>
                                </div>
                                <div class="col-md-4">
                                    <x-form-group>
                                        <x-form-label for="age_band" required="true">Age Band</x-form-label>
                                        <select name="age_band" class="form-select" required>
                                            <option value="">Select age band</option>
                                            @foreach ($ageBands as $band)
                                                <option value="{{ $band }}">{{ $band }}</option>
                                            @endforeach
                                        </select>
                                    </x-form-group>
                                </div>
                                <div class="col-md-3">
                                    <x-form-group>
                                        <x-form-label for="relationship">Relationship</x-form-label>
                                        <input type="text" name="relationship" class="form-control" maxlength="100">
                                    </x-form-group>
                                </div>
                                <div class="col-md-1 d-flex align-items-end mb-5">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('age_band')" />
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        @forelse ($household->careRecipients as $recipient)
                        <div class="border-bottom p-3">
                            @if (auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('Household\\CareRecipientController@update'))
                                <form action="{{ route('households.care-recipients.update', [$household->id, $recipient->id]) }}" method="POST" class="row g-2 align-items-center mb-2">
                                    @csrf @method('PUT')
                                    <div class="col-md-4"><input name="first_name" class="form-control form-control-sm" value="{{ $recipient->first_name }}" maxlength="100" required aria-label="Care recipient first name"></div>
                                    <div class="col-md-3">
                                        <select name="age_band" class="form-select form-select-sm" required aria-label="Care recipient age band">
                                            @foreach ($ageBands as $band)<option value="{{ $band }}" @selected($recipient->age_band === $band)>{{ $band }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3"><input name="relationship" class="form-control form-control-sm" value="{{ $recipient->relationship }}" maxlength="100" aria-label="Relationship"></div>
                                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-light-primary">Save</button></div>
                                </form>
                            @else
                                <strong>{{ $recipient->first_name }}</strong>
                                <span class="badge badge-light-{{ $recipient->recipient_type === 'Child' ? 'warning' : 'info' }} ms-2">{{ $recipient->age_band }}</span>
                                @if ($recipient->relationship)<span class="text-muted ms-1">· {{ $recipient->relationship }}</span>@endif
                            @endif
                            @if ($recipient->is_sensitive)<span class="badge badge-light-danger ms-1">Sensitive</span>@endif
                            @if (auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('Household\\CareRecipientController@destroy'))
                                <form action="{{ route('households.care-recipients.destroy', [$household->id, $recipient->id]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-light-danger btn-sm"
                                        data-confirm="Delete this care recipient?"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            @endif
                        </div>
                        @empty
                        <div class="p-4 text-center text-muted">{{ $restrictedRecipientCount > 0 ? 'No additional care recipient details are available to your role.' : 'No care recipients added yet.' }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ old('service_id') ? 'show active' : '' }}" id="service-requests">
                @if(auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('ServiceRequestController@store'))
                <div class="card mb-4"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Create Service Request</h6></div><div class="card-body">
                    <form method="POST" action="{{ route('households.service-requests.store', $household) }}" id="service-request-form">@csrf
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label" for="service_id">Service</label><select class="form-select" name="service_id" id="service_id" required><option value="">Select a service</option>@foreach($services as $service)<option value="{{ $service->id }}" data-variant="{{ $service->variant }}" data-code="{{ $service->code }}" @selected(old('service_id') == $service->id)>{{ $service->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label" for="live_in_out">Arrangement</label><select class="form-select" name="live_in_out" id="live_in_out" required><option value="">Select</option>@foreach(config('crm.live_in_prefs') as $pref)<option value="{{ $pref }}" @selected(old('live_in_out') === $pref)>{{ ucfirst($pref) }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Days requested</label><div class="d-flex flex-wrap gap-2">@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<label class="form-check"><input class="form-check-input" type="checkbox" name="schedule[weekdays][]" value="{{ $day }}" @checked(in_array($day, old('schedule.weekdays', [])))><span class="form-check-label">{{ $day }}</span></label>@endforeach</div></div>
                            <div class="col-md-3"><label class="form-label">Start time</label><input class="form-control" type="time" name="schedule[start_time]" value="{{ old('schedule.start_time') }}" required></div>
                            <div class="col-md-3"><label class="form-label">End time</label><input class="form-control" type="time" name="schedule[end_time]" value="{{ old('schedule.end_time') }}" required></div>
                        </div>
                        <div class="row g-3 mt-1 d-none" data-assessment="NANNY">
                            <div class="col-md-4"><label class="form-label">Number of children</label><input class="form-control" type="number" min="1" name="assessment[number_of_children]" value="{{ old('assessment.number_of_children') }}"></div>
                            <div class="col-md-4"><label class="form-label">School routines</label><textarea class="form-control" maxlength="1000" name="assessment[school_routines]">{{ old('assessment.school_routines') }}</textarea></div>
                            <div class="col-md-4"><label class="form-label">Play habits</label><textarea class="form-control" maxlength="1000" name="assessment[play_habits]">{{ old('assessment.play_habits') }}</textarea></div>
                        </div>
                        <div class="row g-3 mt-1 d-none" data-assessment="ELDERLY">
                            <div class="col-md-4"><label class="form-label">Mobility levels / support</label><textarea class="form-control" maxlength="1000" name="assessment[mobility_levels]">{{ old('assessment.mobility_levels') }}</textarea></div>
                            <div class="col-md-4"><label class="form-label">Companionship needs</label><textarea class="form-control" maxlength="1000" name="assessment[companionship_needs]">{{ old('assessment.companionship_needs') }}</textarea></div>
                            <div class="col-md-4"><label class="form-label">Dietary restrictions</label><textarea class="form-control" maxlength="1000" name="assessment[dietary_restrictions]">{{ old('assessment.dietary_restrictions') }}</textarea></div>
                            <div class="col-12"><small class="text-muted">Enter non-clinical support needs only. Do not include diagnoses, medication names or dosages, or treatment instructions.</small></div>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit">Create request</button>
                    </form>
                </div></div>
                @endif

                <div class="card"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Household Service Requests</h6></div><div class="card-body">
                    @forelse($household->serviceRequests as $serviceRequest)
                        <div class="border-bottom py-3"><div class="d-flex justify-content-between"><strong>{{ $serviceRequest->service->name }}</strong><span class="badge badge-light-info">{{ $serviceRequest->status }}</span></div>
                            <div class="text-muted small">{{ ucfirst($serviceRequest->live_in_out) }} · {{ implode(', ', $serviceRequest->schedule_json['weekdays'] ?? []) }} · {{ $serviceRequest->schedule_json['start_time'] ?? '' }}–{{ $serviceRequest->schedule_json['end_time'] ?? '' }}</div>
                            @if(!empty($serviceRequest->assessment_json))<dl class="row mt-2 mb-0">@foreach($serviceRequest->assessment_json as $key => $value)<dt class="col-md-4">{{ str($key)->replace('_', ' ')->title() }}</dt><dd class="col-md-8">{{ $value }}</dd>@endforeach</dl>@endif
                            <div class="mt-3">
                                @if(auth()->user()->hasPermission('MatchingController@candidates'))
                                    <a href="{{ route('service-requests.candidates', $serviceRequest) }}" class="btn btn-sm btn-light-primary mb-2">Review preliminary candidates</a>
                                @endif
                                @foreach($serviceRequest->quotes as $quote)
                                    <div class="border rounded p-3 mb-2"><div class="d-flex justify-content-between"><strong>Quote v{{ $quote->version }}</strong><span class="badge {{ $quote->status === 'Approved' ? 'badge-light-success' : 'badge-light-warning' }}">{{ $quote->status }}</span></div>
                                        @foreach($quote->lines as $line)<div class="small text-muted mt-1">{{ $line->description_snapshot }} · Price version #{{ $line->price_version_id }} · {{ $line->quantity }} {{ $line->unit_snapshot }} · ৳{{ $line->unit_amount }}</div>@endforeach
                                        <div class="fw-bold mt-1">{{ $quote->currency }} ৳{{ $quote->final_total ?? $quote->subtotal }}</div>
                                        @if($quote->override_reason)<div class="small">OM override: {{ $quote->override_reason }}</div>@endif
                                        @if($quote->status === 'Draft' && auth()->user()->hasPermission('QuoteController@approve'))
                                            <form class="row g-2 mt-2" method="POST" action="{{ route('quotes.approve', $quote) }}">@csrf
                                                <div class="col-md-4"><label class="form-label small">Optional overridden total (BDT)</label><input class="form-control form-control-sm" type="number" name="override_total" min="0.01" max="9999999999.99" step="0.01"></div>
                                                <div class="col-md-6"><label class="form-label small">Override reason, when overriding</label><input class="form-control form-control-sm" name="override_reason" maxlength="500"></div>
                                                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-primary">Approve</button></div>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                                @if(auth()->user()->hasPermission('QuoteController@store'))
                                    <form method="POST" action="{{ route('service-requests.quotes.store', $serviceRequest) }}">@csrf<button class="btn btn-sm btn-light-primary">Generate quote from current approved price</button></form>
                                @endif
                                @foreach($serviceRequest->proposals as $proposal)
                                    <div class="border rounded p-3 mb-2">
                                        <div class="d-flex justify-content-between"><strong>Proposal #{{ $proposal->id }}</strong><span class="badge {{ $proposal->status === 'Draft' ? 'badge-light-warning' : ($proposal->status === 'Placed' ? 'badge-light-success' : 'badge-light-secondary') }}">{{ $proposal->status }}</span></div>
                                        <p class="mb-1 mt-2">{{ $proposal->rationale }}</p>
                                        @if($proposal->status === 'Draft' && (auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('PlacementController@familySummary')))
                                            <a class="btn btn-sm btn-light-primary mb-2" href="{{ route('proposals.family-summary', $proposal) }}">Download redacted family summary</a>
                                        @endif
                                        <div class="small text-muted">Selected candidates:</div>
                                        @foreach($proposal->candidates as $proposalCandidate)
                                            @php($familyDecision = $proposal->events->first(fn ($event) => (int) $event->match_proposal_candidate_id === (int) $proposalCandidate->id && $event->event_type === 'FamilyDecisionRecorded'))
                                            @php($caregiverResponse = $proposal->events->first(fn ($event) => (int) $event->match_proposal_candidate_id === (int) $proposalCandidate->id && $event->event_type === 'CaregiverResponseRecorded'))
                                            <div class="border-top mt-2 pt-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span>{{ $proposalCandidate->caregiver->name ?? 'Caregiver record unavailable' }}</span>
                                                    @if($familyDecision)<span class="badge {{ $familyDecision->outcome === 'Accepted' ? 'badge-light-success' : 'badge-light-secondary' }}">Family: {{ $familyDecision->outcome }}</span>@endif
                                                </div>
                                                @if($familyDecision)
                                                    <div class="small text-muted">{{ $familyDecision->rationale }}</div>
                                                @elseif($proposal->status === 'Draft' && (auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('ProposalController@recordFamilyDecision')))
                                                    <form class="row g-2 mt-1" method="POST" action="{{ route('proposal-candidates.family-decision', $proposalCandidate) }}">
                                                        @csrf
                                                        <div class="col-md-2"><select class="form-select form-select-sm" name="outcome" required><option value="">Record outcome</option><option value="Accepted">Accepted</option><option value="Declined">Declined</option></select></div>
                                                        <div class="col-md-8"><input class="form-control form-control-sm" name="rationale" minlength="20" maxlength="1000" placeholder="Decision details (20–1000 characters)" required></div>
                                                        <div class="col-md-2"><button class="btn btn-sm btn-light-primary">Record response</button></div>
                                                        <div class="col-12"><small class="text-muted">Record a family response received outside this application. This proposal draft has not been sent by the system.</small></div>
                                                    </form>
                                                @endif
                                                @if($caregiverResponse)
                                                    <div class="small mt-2"><strong>Caregiver response:</strong> {{ $caregiverResponse->outcome }} — {{ $caregiverResponse->rationale }}</div>
                                                @elseif($proposal->status === 'Draft' && (auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('ProposalController@recordCaregiverResponse')))
                                                    <form class="row g-2 mt-2" method="POST" action="{{ route('proposal-candidates.caregiver-response', $proposalCandidate) }}">
                                                        @csrf
                                                        <div class="col-md-2"><select class="form-select form-select-sm" name="outcome" required><option value="">Caregiver response</option><option value="Accepted">Accepted</option><option value="Declined">Declined</option></select></div>
                                                        <div class="col-md-8"><input class="form-control form-control-sm" name="rationale" minlength="20" maxlength="1000" placeholder="Response details (20–1000 characters)" required></div>
                                                        <div class="col-md-2"><button class="btn btn-sm btn-light-primary">Record response</button></div>
                                                    </form>
                                                @endif
                                                @if($proposal->status === 'Draft' && $familyDecision?->outcome === 'Accepted' && !$proposalCandidate->placement && (auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('PlacementController@activate')))
                                                    @if($serviceRequest->quotes->contains(fn ($quote) => $quote->status === 'Approved'))
                                                        <form class="row g-2 mt-2" method="POST" enctype="multipart/form-data" action="{{ route('proposal-candidates.placement.activate', $proposalCandidate) }}">
                                                            @csrf
                                                            <div class="col-md-3"><label class="form-label small" for="placement-start-{{ $proposalCandidate->id }}">Start date</label><input id="placement-start-{{ $proposalCandidate->id }}" class="form-control form-control-sm" type="date" name="start_date" min="{{ now()->toDateString() }}" required></div>
                                                            <div class="col-md-3"><label class="form-label small" for="placement-end-{{ $proposalCandidate->id }}">End date (optional)</label><input id="placement-end-{{ $proposalCandidate->id }}" class="form-control form-control-sm" type="date" name="end_date" min="{{ now()->toDateString() }}"></div>
                                                            <div class="col-md-4"><label class="form-label small" for="placement-agreement-{{ $proposalCandidate->id }}">Signed agreement (PDF/JPEG/PNG, max 10 MB)</label><input id="placement-agreement-{{ $proposalCandidate->id }}" class="form-control form-control-sm" type="file" name="agreement" accept="application/pdf,image/jpeg,image/png" required></div>
                                                            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-primary">Activate placement</button></div>
                                                            <div class="col-12"><small class="text-muted">Activation checks current caregiver readiness, approved effective pricing, agreement malware scan, and weekday/time overlap.</small></div>
                                                        </form>
                                                    @else
                                                        <div class="small text-warning mt-2">Approve a current quote before placement activation.</div>
                                                    @endif
                                                @elseif($proposalCandidate->placement)
                                                    <div class="small text-success mt-2">Placement #{{ $proposalCandidate->placement->id }} — {{ $proposalCandidate->placement->status }} · starts {{ $proposalCandidate->placement->start_date->format('d M Y') }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                        <div class="small text-muted">History: {{ $proposal->events->pluck('event_type')->implode(' → ') }}</div>
                                    </div>
                                @endforeach
                                @foreach($serviceRequest->placements as $placement)
                                    <div class="border rounded p-3 mb-2 border-success">
                                        <div class="d-flex justify-content-between"><strong>Placement #{{ $placement->id }}</strong><span class="badge {{ $placement->status === 'Active' ? 'badge-light-success' : 'badge-light-info' }}">{{ $placement->status }}</span></div>
                                        <div class="small mt-2">{{ $placement->caregiver->name ?? 'Caregiver record unavailable' }} · {{ $placement->start_date->format('d M Y') }}{{ $placement->end_date ? ' to '.$placement->end_date->format('d M Y') : ' onward' }} · {{ implode(', ', $placement->schedule_snapshot['weekdays'] ?? []) }} {{ $placement->schedule_snapshot['start_time'] ?? '' }}–{{ $placement->schedule_snapshot['end_time'] ?? '' }}</div>
                                        <div class="small text-muted">Quote #{{ $placement->quote_id }} · Price version #{{ $placement->price_version_id }} · {{ $placement->currency }} {{ $placement->quote_total_snapshot }}</div>
                                        @if(auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('PlacementController@revealAgreement'))
                                            <form class="row g-2 mt-2" method="POST" action="{{ route('placements.agreement.reveal', $placement) }}">
                                                @csrf
                                                <div class="col-md-9"><input class="form-control form-control-sm" name="reason" minlength="20" maxlength="500" placeholder="Reason to access the signed agreement (20–500 characters)" required></div>
                                                <div class="col-md-3"><button class="btn btn-sm btn-light-primary">Reveal agreement (5 minutes)</button></div>
                                            </form>
                                        @endif
                                        @if(in_array($placement->status, ['Active', 'Scheduled', 'Paused'], true) && (auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('PlacementController@transition')))
                                            <form class="row g-2 mt-2" method="POST" action="{{ route('placements.transition', $placement) }}">
                                                @csrf
                                                <div class="col-md-3"><select class="form-select form-select-sm" name="status" required><option value="">Update placement status</option>@if(in_array($placement->status, ['Active', 'Scheduled'], true))<option value="Paused">Pause</option>@endif@if(in_array($placement->status, ['Active', 'Paused'], true))<option value="Ended">End</option>@endif<option value="Cancelled">Cancel</option></select></div>
                                                <div class="col-md-7"><input class="form-control form-control-sm" name="rationale" minlength="20" maxlength="1000" placeholder="Reason (20–1000 characters)" required></div>
                                                <div class="col-md-2"><button class="btn btn-sm btn-light-primary">Record change</button></div>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty<p class="text-muted mb-0">No service requests recorded.</p>@endforelse
                </div></div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', function () {
        const service = document.getElementById('service_id');
        const arrangement = document.getElementById('live_in_out');
        const form = document.getElementById('service-request-form');
        if (!service || !arrangement || !form) return;
        function syncServiceFields() {
            const selected = service.options[service.selectedIndex];
            const variant = selected?.dataset.variant;
            const code = selected?.dataset.code;
            if (variant && variant !== 'n/a') {
                arrangement.value = variant;
                arrangement.disabled = true;
                let input = form.querySelector('input[name="live_in_out"][type="hidden"]');
                if (!input) { input = document.createElement('input'); input.type = 'hidden'; input.name = 'live_in_out'; form.appendChild(input); }
                input.value = variant;
            } else {
                arrangement.disabled = false;
                form.querySelector('input[name="live_in_out"][type="hidden"]')?.remove();
                if (arrangement.value === 'live-in' || arrangement.value === 'live-out') arrangement.value = '';
            }
            form.querySelectorAll('[data-assessment]').forEach(el => {
                const active = el.dataset.assessment === code;
                el.classList.toggle('d-none', !active);
                el.querySelectorAll('input, textarea').forEach(field => {
                    field.disabled = !active;
                    field.required = active && field.name === 'assessment[number_of_children]' && code === 'NANNY';
                });
            });
        }
        service.addEventListener('change', syncServiceFields);
        syncServiceFields();
    });
</script>
@endpush
