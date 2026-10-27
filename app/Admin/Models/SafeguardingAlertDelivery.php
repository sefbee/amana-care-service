<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafeguardingAlertDelivery extends Model
{
    protected $fillable = ['incident_alert_id', 'channel', 'status', 'attempt_count', 'next_attempt_at', 'delivered_at', 'last_error_code'];

    protected function casts(): array
    {
        return ['next_attempt_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (SafeguardingAlertDelivery $delivery): void {
            if ($delivery->isDirty(['incident_alert_id', 'channel'])) {
                throw new \LogicException('Safeguarding notification routing is immutable.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Safeguarding delivery history cannot be deleted.'));
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(SafeguardingIncidentAlert::class, 'incident_alert_id');
    }
}
