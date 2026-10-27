<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafeguardingIncident extends Model
{
    public const CLASSIFICATION_RESTRICTED = 'Restricted';

    protected $fillable = [
        'classification', 'category', 'summary', 'details', 'household_id', 'placement_id',
        'caregiver_id', 'reported_by', 'occurred_at', 'caregiver_held_at', 'caregiver_held_by',
    ];

    protected $hidden = ['category', 'summary', 'details'];

    protected function casts(): array
    {
        return ['category' => 'encrypted', 'summary' => 'encrypted', 'details' => 'encrypted', 'occurred_at' => 'datetime', 'caregiver_held_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (SafeguardingIncident $incident): void {
            $incident->classification = self::CLASSIFICATION_RESTRICTED;
        });
        static::updating(function (SafeguardingIncident $incident): void {
            if ($incident->isDirty(['classification', 'category', 'summary', 'details', 'household_id', 'placement_id', 'caregiver_id', 'reported_by', 'occurred_at'])) {
                throw new \LogicException('Restricted incident reports are immutable. Add a separately audited event for subsequent actions.');
            }
            if ($incident->getOriginal('caregiver_held_at') !== null && $incident->isDirty(['caregiver_held_at', 'caregiver_held_by'])) {
                throw new \LogicException('A safeguarding caregiver hold cannot be removed or reassigned.');
            }
            if ($incident->isDirty(['caregiver_held_at', 'caregiver_held_by']) && ($incident->caregiver_held_at === null || $incident->caregiver_held_by === null)) {
                throw new \LogicException('A caregiver hold requires an actor and timestamp.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Safeguarding incident records cannot be deleted.'));
    }

    public function household(): BelongsTo { return $this->belongsTo(Household::class); }
    public function placement(): BelongsTo { return $this->belongsTo(Placement::class); }
    public function caregiver(): BelongsTo { return $this->belongsTo(Caregiver::class)->withTrashed(); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function caregiverHoldActor(): BelongsTo { return $this->belongsTo(User::class, 'caregiver_held_by'); }
    public function alerts(): HasMany { return $this->hasMany(SafeguardingIncidentAlert::class, 'incident_id'); }
}
