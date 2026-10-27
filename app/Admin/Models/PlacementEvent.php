<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementEvent extends Model
{
    protected $fillable = ['placement_id', 'event_type', 'rationale', 'actor_id', 'occurred_at'];
    protected function casts(): array { return ['occurred_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Placement event history is immutable.'));
        static::deleting(fn () => throw new \LogicException('Placement event history is immutable.'));
    }
    public function placement(): BelongsTo { return $this->belongsTo(Placement::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
