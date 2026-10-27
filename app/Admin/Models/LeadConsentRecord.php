<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadConsentRecord extends Model
{
    use HasFactory;

    // No SoftDeletes — append-only audit table

    protected $fillable = [
        'lead_id', 'channel', 'status', 'source',
        'consented_at', 'withdrawn_at', 'created_by',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Consent history is append-only; record a new consent event.'));
        static::deleting(fn () => throw new \LogicException('Consent history cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
