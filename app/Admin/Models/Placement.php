<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Placement extends Model
{
    protected $fillable = [
        'service_request_id', 'match_proposal_candidate_id', 'household_id', 'caregiver_id',
        'quote_id', 'price_version_id', 'price_amount_snapshot', 'quote_total_snapshot', 'currency', 'start_date',
        'end_date', 'schedule_snapshot', 'status', 'agreement_disk', 'agreement_key',
        'agreement_mime', 'agreement_size_bytes', 'agreement_sha256', 'activated_by',
        'activated_at', 'paused_at', 'ended_at',
    ];

    protected $hidden = ['agreement_key', 'agreement_sha256'];

    protected function casts(): array
    {
        return [
            'price_amount_snapshot' => 'decimal:2',
            'quote_total_snapshot' => 'decimal:2',
            'schedule_snapshot' => 'array',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'activated_at' => 'datetime',
            'paused_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Placement and agreement history cannot be deleted.'));
        static::updating(function (Placement $placement): void {
            $immutable = ['service_request_id', 'match_proposal_candidate_id', 'household_id', 'caregiver_id', 'quote_id', 'price_version_id', 'price_amount_snapshot', 'quote_total_snapshot', 'currency', 'start_date', 'end_date', 'schedule_snapshot', 'agreement_disk', 'agreement_key', 'agreement_mime', 'agreement_size_bytes', 'agreement_sha256', 'activated_by'];
            if (array_intersect(array_keys($placement->getDirty()), $immutable) !== []) {
                throw new \LogicException('Activated placement terms and agreement reference are immutable.');
            }
            if ($placement->isDirty('activated_at') && ! (
                $placement->getOriginal('status') === 'Scheduled'
                && $placement->status === 'Active'
                && $placement->getOriginal('activated_at') === null
                && $placement->activated_at !== null
            )) {
                throw new \LogicException('The activation timestamp can only be recorded when a scheduled placement becomes active.');
            }
            if ($placement->isDirty('status')) {
                $allowed = [
                    'Active' => ['Paused', 'Ended', 'Cancelled'],
                    'Scheduled' => ['Active', 'Paused', 'Cancelled'],
                    'Paused' => ['Ended', 'Cancelled'],
                ];
                if (! in_array($placement->status, $allowed[$placement->getOriginal('status')] ?? [], true)) {
                    throw new \LogicException('Invalid placement lifecycle transition.');
                }
            }
        });
    }

    public function serviceRequest(): BelongsTo { return $this->belongsTo(ServiceRequest::class); }
    public function candidate(): BelongsTo { return $this->belongsTo(MatchProposalCandidate::class, 'match_proposal_candidate_id'); }
    public function household(): BelongsTo { return $this->belongsTo(Household::class); }
    public function caregiver(): BelongsTo { return $this->belongsTo(Caregiver::class)->withTrashed(); }
    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }
    public function priceVersion(): BelongsTo { return $this->belongsTo(PriceVersion::class); }
    public function slots(): HasMany { return $this->hasMany(PlacementScheduleSlot::class); }
    public function events(): HasMany { return $this->hasMany(PlacementEvent::class)->orderBy('occurred_at'); }
    public function agreementReveals(): HasMany { return $this->hasMany(PlacementAgreementReveal::class)->latest('attempted_at'); }
    public function followUpTasks(): HasMany { return $this->hasMany(PlacementFollowUpTask::class)->orderBy('milestone_day'); }
}
