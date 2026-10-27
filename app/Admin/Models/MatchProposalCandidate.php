<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MatchProposalCandidate extends Model
{
    protected $fillable = ['match_proposal_id', 'caregiver_id', 'position'];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(MatchProposal::class, 'match_proposal_id');
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class)->withTrashed();
    }

    public function placement(): HasOne
    {
        return $this->hasOne(Placement::class, 'match_proposal_candidate_id');
    }
}
