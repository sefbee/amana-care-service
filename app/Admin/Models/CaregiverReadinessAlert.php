<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverReadinessAlert extends Model
{
    protected $fillable = ['caregiver_id', 'screening_check_id', 'alert_type', 'valid_until', 'read_at', 'read_by'];
    protected function casts(): array { return ['valid_until' => 'date:Y-m-d', 'read_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::updating(function (CaregiverReadinessAlert $alert): void {
            if ($alert->getOriginal('read_at') !== null || array_diff(array_keys($alert->getDirty()), ['read_at', 'read_by']) !== []) {
                throw new \LogicException('Readiness alerts are immutable after acknowledgement.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Readiness alert history cannot be deleted.'));
    }
    public function caregiver(): BelongsTo { return $this->belongsTo(Caregiver::class)->withTrashed(); }
    public function check(): BelongsTo { return $this->belongsTo(CaregiverScreeningCheck::class, 'screening_check_id'); }
    public function reader(): BelongsTo { return $this->belongsTo(User::class, 'read_by'); }
}
