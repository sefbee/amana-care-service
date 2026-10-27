<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementAgreementReveal extends Model
{
    protected $fillable = ['placement_id', 'actor_id', 'reason', 'attempted_at', 'expires_at', 'result', 'ip_address'];
    protected function casts(): array { return ['attempted_at' => 'datetime', 'expires_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Agreement reveal attempts are immutable.'));
        static::deleting(fn () => throw new \LogicException('Agreement reveal attempts are immutable.'));
    }
    public function placement(): BelongsTo { return $this->belongsTo(Placement::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
