<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchProposalEvent extends Model
{
    protected $fillable = ['match_proposal_id', 'match_proposal_candidate_id', 'event_type', 'outcome', 'rationale', 'actor_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Proposal event history is immutable.'));
        static::deleting(fn () => throw new \LogicException('Proposal event history is immutable.'));
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(MatchProposal::class, 'match_proposal_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(MatchProposalCandidate::class, 'match_proposal_candidate_id');
    }
}
