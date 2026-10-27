<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafeguardingIncidentAlert extends Model
{
    protected $fillable = ['incident_id', 'recipient_id', 'priority', 'acknowledged_at', 'acknowledged_by'];

    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime', 'sla_breached_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (SafeguardingIncidentAlert $alert): void {
            if ($alert->isDirty(['incident_id', 'recipient_id', 'priority'])) {
                throw new \LogicException('Safeguarding alert routing and priority are immutable.');
            }
            if ($alert->getOriginal('acknowledged_at') !== null || ($alert->isDirty(['acknowledged_at', 'acknowledged_by']) && ($alert->acknowledged_at === null || $alert->acknowledged_by === null))) {
                throw new \LogicException('Safeguarding alert acknowledgement is immutable.');
            }
            if ($alert->getOriginal('sla_breached_at') !== null || ($alert->isDirty('sla_breached_at') && $alert->sla_breached_at === null)) {
                throw new \LogicException('Safeguarding acknowledgement SLA breach history is immutable.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Safeguarding alerts cannot be deleted.'));
    }

    public function incident(): BelongsTo { return $this->belongsTo(SafeguardingIncident::class, 'incident_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_id'); }
    public function acknowledger(): BelongsTo { return $this->belongsTo(User::class, 'acknowledged_by'); }
    public function deliveries(): HasMany { return $this->hasMany(SafeguardingAlertDelivery::class, 'incident_alert_id'); }
}
