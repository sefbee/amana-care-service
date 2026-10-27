<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\MatchProposal;
use App\Admin\Models\MatchProposalCandidate;
use App\Admin\Models\MatchProposalEvent;
use App\Admin\Models\ServiceRequest;
use App\Admin\Models\TrainingModule;
use App\Admin\Services\CandidateMatcher;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ProposalController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest, CrmRecordAccess $access, CandidateMatcher $matcher)
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['co', 'platform-superuser'], true), 403);
        $data = $request->validate([
            'candidate_ids' => ['required', 'array', 'min:1', 'max:3'],
            'candidate_ids.*' => ['required', 'integer', 'distinct', 'exists:caregivers,id'],
            'rationale' => ['required', 'string', 'min:20', 'max:2000'],
        ]);
        $candidateIds = array_values(array_unique(array_map('intval', $data['candidate_ids'])));

        $proposal = DB::transaction(function () use ($request, $serviceRequest, $access, $matcher, $candidateIds, $data): MatchProposal {
            $lockedRequest = ServiceRequest::query()->lockForUpdate()->findOrFail($serviceRequest->id);
            $lockedRequest->load(['household', 'service']);
            $access->assertHousehold($request->user(), $lockedRequest->household);

            $mandatoryTrainingIds = TrainingModule::query()->where('is_active', true)->where('is_mandatory', true)->pluck('id');
            $filtered = $matcher->filter($lockedRequest, $mandatoryTrainingIds, lockCandidates: true, actorId: (int) $request->user()->id, actor: $request->user());
            $eligibleIds = $filtered['included']->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (array_diff($candidateIds, $eligibleIds) !== []) {
                throw ValidationException::withMessages(['candidate_ids' => 'One or more selected caregivers no longer meet the current preliminary readiness, service skill and arrangement filters. Refresh the candidate list.']);
            }

            $proposal = MatchProposal::query()->create([
                'service_request_id' => $lockedRequest->id,
                'rationale' => trim($data['rationale']),
                'status' => 'Draft',
                'created_by' => $request->user()->id,
            ]);

            foreach ($candidateIds as $position => $caregiverId) {
                MatchProposalCandidate::query()->create([
                    'match_proposal_id' => $proposal->id,
                    'caregiver_id' => $caregiverId,
                    'position' => $position + 1,
                ]);
            }

            MatchProposalEvent::query()->create([
                'match_proposal_id' => $proposal->id,
                'event_type' => 'DraftCreated',
                'actor_id' => $request->user()->id,
                'occurred_at' => now(),
            ]);
            activity('matching_proposal')->causedBy($request->user())->performedOn($proposal)
                ->withProperties(['service_request_id' => $lockedRequest->id, 'candidate_ids' => $candidateIds, 'rationale_length' => mb_strlen(trim($data['rationale']))])
                ->log('Human-reviewed match proposal draft created');

            return $proposal;
        });

        return redirect()->route('households.show', $serviceRequest->household_id)->with('success', 'Proposal draft saved. It has not been sent to the family.');
    }

    public function recordFamilyDecision(Request $request, MatchProposalCandidate $candidate, CrmRecordAccess $access)
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['co', 'platform-superuser'], true), 403);
        $candidate->load(['proposal.serviceRequest.household']);
        $proposal = $candidate->proposal;
        $access->assertHousehold($request->user(), $proposal->serviceRequest->household);
        $candidate->loadMissing('caregiver');
        $access->assertCaregiver($request->user(), $candidate->caregiver);

        $data = $request->validate([
            'outcome' => ['required', Rule::in(['Accepted', 'Declined'])],
            'rationale' => ['required', 'string', 'min:20', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $candidate, $proposal, $data, $access): void {
            $lockedProposal = MatchProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            $lockedProposal->load('serviceRequest.household');
            $access->assertHousehold($request->user(), $lockedProposal->serviceRequest->household);
            abort_unless($lockedProposal->status === 'Draft', 422, 'Only a draft proposal can receive a family decision.');
            $lockedCandidate = MatchProposalCandidate::query()->lockForUpdate()->findOrFail($candidate->id);
            $lockedCandidate->loadMissing('caregiver');
            $access->assertCaregiver($request->user(), $lockedCandidate->caregiver);
            $alreadyRecorded = MatchProposalEvent::query()
                ->where('match_proposal_candidate_id', $lockedCandidate->id)
                ->where('event_type', 'FamilyDecisionRecorded')
                ->exists();
            abort_if($alreadyRecorded, 422, 'A family decision is already recorded for this candidate.');

            MatchProposalEvent::query()->create([
                'match_proposal_id' => $lockedProposal->id,
                'match_proposal_candidate_id' => $lockedCandidate->id,
                'event_type' => 'FamilyDecisionRecorded',
                'outcome' => $data['outcome'],
                'rationale' => trim($data['rationale']),
                'actor_id' => $request->user()->id,
                'occurred_at' => now(),
            ]);

            activity('matching_proposal')->causedBy($request->user())->performedOn($lockedProposal)
                ->withProperties([
                    'candidate_id' => $lockedCandidate->id,
                    'outcome' => $data['outcome'],
                    'rationale_length' => mb_strlen(trim($data['rationale'])),
                ])->log('Family decision recorded against proposed caregiver');
        });

        return redirect()->route('households.show', $proposal->serviceRequest->household_id)->with('success', 'Family decision recorded in proposal history.');
    }

    public function recordCaregiverResponse(Request $request, MatchProposalCandidate $candidate, CrmRecordAccess $access)
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['co', 'om', 'platform-superuser'], true), 403);
        $candidate->load(['proposal.serviceRequest.household']);
        $proposal = $candidate->proposal;
        $access->assertHousehold($request->user(), $proposal->serviceRequest->household);
        $candidate->loadMissing('caregiver');
        $access->assertCaregiver($request->user(), $candidate->caregiver);
        $data = $request->validate([
            'outcome' => ['required', Rule::in(['Accepted', 'Declined'])],
            'rationale' => ['required', 'string', 'min:20', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $candidate, $proposal, $data, $access): void {
            $lockedProposal = MatchProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            $lockedProposal->load('serviceRequest.household');
            $access->assertHousehold($request->user(), $lockedProposal->serviceRequest->household);
            abort_unless($lockedProposal->status === 'Draft', 422, 'Only an active draft proposal can receive a caregiver response.');
            $lockedCandidate = MatchProposalCandidate::query()->lockForUpdate()->findOrFail($candidate->id);
            $lockedCandidate->loadMissing('caregiver');
            $access->assertCaregiver($request->user(), $lockedCandidate->caregiver);
            $alreadyRecorded = MatchProposalEvent::query()
                ->where('match_proposal_candidate_id', $lockedCandidate->id)
                ->where('event_type', 'CaregiverResponseRecorded')
                ->exists();
            abort_if($alreadyRecorded, 422, 'A caregiver response is already recorded for this candidate.');

            MatchProposalEvent::query()->create([
                'match_proposal_id' => $lockedProposal->id,
                'match_proposal_candidate_id' => $lockedCandidate->id,
                'event_type' => 'CaregiverResponseRecorded',
                'outcome' => $data['outcome'],
                'rationale' => trim($data['rationale']),
                'actor_id' => $request->user()->id,
                'occurred_at' => now(),
            ]);
            activity('matching_proposal')->causedBy($request->user())->performedOn($lockedProposal)
                ->withProperties([
                    'candidate_id' => $lockedCandidate->id,
                    'outcome' => $data['outcome'],
                    'rationale_length' => mb_strlen(trim($data['rationale'])),
                ])->log('Caregiver response recorded against proposal');
        });

        return redirect()->route('households.show', $proposal->serviceRequest->household_id)->with('success', 'Caregiver response recorded in proposal history.');
    }
}
