<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordConflictBlock extends Model
{
    protected $fillable = [
        'staff_user_id', 'household_id', 'caregiver_id', 'imposed_by', 'reason',
        'revoked_at', 'revoked_by', 'revocation_reason',
    ];

    protected function casts(): array
    {
        return [
            'reason' => 'encrypted',
            'revocation_reason' => 'encrypted',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RecordConflictBlock $block): void {
            if ((bool) $block->household_id === (bool) $block->caregiver_id) {
                throw new \LogicException('A conflict block must target exactly one household or caregiver.');
            }
        });
        static::updating(function (RecordConflictBlock $block): void {
            if ($block->isDirty(['staff_user_id', 'household_id', 'caregiver_id', 'imposed_by', 'reason'])) {
                throw new \LogicException('Conflict block history is immutable; revoke and create a new record.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Conflict block history cannot be deleted.'));
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function imposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imposed_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
