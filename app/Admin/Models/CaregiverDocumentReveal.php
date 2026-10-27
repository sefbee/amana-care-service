<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverDocumentReveal extends Model
{
    protected $fillable = ['caregiver_document_id', 'actor_id', 'reason_code', 'attempted_at', 'expires_at', 'result', 'ip_address'];

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Document reveal audit records are immutable.'));
        static::deleting(fn () => throw new \LogicException('Document reveal audit records are immutable.'));
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(CaregiverDocument::class, 'caregiver_document_id');
    }
}
