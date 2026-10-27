<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalNotification extends Model
{
    public const CHANNELS = ['in_app', 'email', 'sms'];

    protected $fillable = [
        'notification_type', 'recipient_user_id', 'lead_id', 'created_by_user_id', 'created_by_role_id', 'channel', 'priority',
        'idempotency_key', 'title', 'body', 'status', 'attempt_count', 'next_attempt_at', 'reserved_at',
        'delivered_at', 'read_at', 'last_error_code',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'encrypted',
            'body' => 'encrypted',
            'next_attempt_at' => 'datetime',
            'reserved_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OperationalNotification $notification): void {
            $hasUser = $notification->recipient_user_id !== null;
            $hasLead = $notification->lead_id !== null;
            abort_unless($hasUser !== $hasLead, 422, 'A notification must have exactly one recipient.');
            abort_unless(in_array($notification->channel, self::CHANNELS, true), 422, 'Unsupported notification channel.');
            abort_unless(in_array($notification->priority, ['normal', 'high'], true), 422, 'Unsupported notification priority.');
            abort_unless(! ($notification->channel === 'in_app' && ! $hasUser), 422, 'In-app notifications require a staff recipient.');
            if ($notification->channel === 'in_app') {
                $notification->status = 'Delivered';
                $notification->delivered_at ??= now();
            }
        });

        static::updating(function (OperationalNotification $notification): void {
            if ($notification->isDirty([
                'notification_type', 'recipient_user_id', 'lead_id', 'created_by_user_id', 'created_by_role_id', 'channel',
                'priority', 'idempotency_key', 'title', 'body',
            ])) {
                throw new \LogicException('Notification content and routing are immutable after enqueue.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Notification delivery history cannot be deleted.'));
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
