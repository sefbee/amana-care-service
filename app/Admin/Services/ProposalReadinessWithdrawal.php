<?php

namespace App\Admin\Services;

use App\Admin\Models\Caregiver;
use App\Admin\Models\MatchProposal;
use App\Admin\Models\MatchProposalCandidate;
use App\Admin\Models\MatchProposalEvent;
use Illuminate\Support\Facades\DB;

class ProposalReadinessWithdrawal
{
    public function withdrawDrafts(Caregiver $caregiver, ?int $actorId = null): int
    {
        return DB::transaction(function () use ($caregiver, $actorId): int {
            $proposals = MatchProposal::query()->where('status', 'Draft')
                ->whereHas('candidates', fn ($query) => $query->where('caregiver_id', $caregiver->id))
                ->lockForUpdate()->get();
            foreach ($proposals as $proposal) {
                $proposal->forceFill(['status' => 'Withdrawn'])->save();
                $candidates = MatchProposalCandidate::query()
                    ->where('match_proposal_id', $proposal->id)->where('caregiver_id', $caregiver->id)->get();
                foreach ($candidates as $candidate) {
                    MatchProposalEvent::query()->create([
                        'match_proposal_id' => $proposal->id,
                        'match_proposal_candidate_id' => $candidate->id,
                        'event_type' => 'ReadinessRevoked',
                        'outcome' => 'Withdrawn',
                        'rationale' => 'The caregiver no longer meets the current readiness requirements.',
                        'actor_id' => $actorId,
                        'occurred_at' => now(),
                    ]);
                }
                $activity = activity('matching_proposal')->performedOn($proposal);
                if ($actorId) $activity->causedBy($actorId);
                $activity->withProperties(['caregiver_id' => $caregiver->id, 'reason' => 'readiness_revoked'])
                    ->log('Draft proposal withdrawn because candidate readiness was revoked');
            }

            return $proposals->count();
        });
    }
}
