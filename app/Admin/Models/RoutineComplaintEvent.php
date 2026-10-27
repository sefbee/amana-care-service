<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutineComplaintEvent extends Model
{
    protected $table = 'routine_complaint_events';
    protected $fillable = ['complaint_id', 'actor_id', 'event_type', 'from_value', 'to_value', 'rationale', 'occurred_at'];

    protected function casts(): array
    {
        return ['rationale' => 'encrypted', 'occurred_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Complaint history is append-only.'));
        static::deleting(fn () => throw new \LogicException('Complaint history cannot be deleted.'));
    }

    public function complaint(): BelongsTo { return $this->belongsTo(RoutineComplaint::class, 'complaint_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
    public function previousAssignee(): BelongsTo { return $this->belongsTo(User::class, 'from_value'); }
    public function nextAssignee(): BelongsTo { return $this->belongsTo(User::class, 'to_value'); }
}
