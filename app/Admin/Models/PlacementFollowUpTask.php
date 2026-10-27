<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlacementFollowUpTask extends Model
{
    protected $fillable = ['placement_id', 'milestone_day', 'due_at', 'status', 'completed_by', 'completed_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Follow-up work history cannot be deleted.'));
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PlacementFollowUpAttempt::class, 'follow_up_task_id')->orderBy('attempted_at');
    }

    public function latestAttempt(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PlacementFollowUpAttempt::class, 'follow_up_task_id')->latestOfMany();
    }
}
