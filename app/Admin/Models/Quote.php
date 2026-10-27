<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    protected $fillable = [
        'service_request_id', 'version', 'created_by', 'approved_by', 'currency',
        'subtotal', 'final_total', 'status', 'override_reason', 'approved_at',
    ];

    protected static function booted(): void
    {
        static::updating(function (Quote $quote): void {
            if ($quote->getOriginal('status') === 'Approved' && $quote->isDirty()) {
                throw new \LogicException('Approved quote snapshots are immutable. Create a new quote version.');
            }
        });
        static::deleting(function (Quote $quote): void {
            if ($quote->status === 'Approved') {
                throw new \LogicException('Approved quote history cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'final_total' => 'decimal:2', 'approved_at' => 'datetime'];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
