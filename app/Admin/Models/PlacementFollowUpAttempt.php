<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementFollowUpAttempt extends Model
{
    protected $fillable = ['follow_up_task_id', 'attempted_by', 'attempted_at', 'outcome', 'notes'];

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime', 'notes' => 'encrypted'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Follow-up attempt history is immutable.'));
        static::deleting(fn () => throw new \LogicException('Follow-up attempt history is immutable.'));
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(PlacementFollowUpTask::class, 'follow_up_task_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attempted_by');
    }
}
