<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Admin\Models\User;

class CaregiverScreeningCheck extends Model
{
    protected $fillable = [
        'caregiver_id', 'type', 'outcome', 'checked_at', 'valid_until',
        'evidence_document_id', 'reviewed_by', 'verification_status', 'record_source',
        'approved_by', 'approved_at', 'approval_reason',
    ];

    protected function casts(): array
    {
        return ['checked_at' => 'date:Y-m-d', 'valid_until' => 'date:Y-m-d', 'approved_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (CaregiverScreeningCheck $check): void {
            if ($check->isDirty('verification_status') || $check->isDirty('record_source')) {
                throw new \LogicException('A screening record verification state and source are immutable; record a new check after evidence review.');
            }

            $resultFields = ['caregiver_id', 'type', 'outcome', 'checked_at', 'valid_until', 'evidence_document_id', 'reviewed_by', 'verification_status', 'record_source'];
            if ($check->getOriginal('approved_at') !== null && $check->isDirty($resultFields)) {
                throw new \LogicException('An approved screening result is immutable; record a new check for later evidence.');
            }

            if ($check->isDirty(['approved_by', 'approved_at', 'approval_reason'])) {
                if ($check->getOriginal('approved_at') !== null
                    || ! $check->approved_by
                    || ! $check->approved_at
                    || trim((string) $check->approval_reason) === '') {
                    throw new \LogicException('Screening approval requires one actor, timestamp and reason and cannot be changed.');
                }
            }
        });
        static::deleting(fn () => throw new \LogicException('Screening results and approvals cannot be deleted.'));
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(CaregiverDocument::class, 'evidence_document_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
