<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchProposal extends Model
{
    protected $fillable = ['service_request_id', 'rationale', 'status', 'created_by'];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Proposal history cannot be deleted.'));
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(MatchProposalCandidate::class)->orderBy('position');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchProposalEvent::class)->orderBy('occurred_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
