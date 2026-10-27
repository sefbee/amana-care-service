<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoutineComplaint extends Model
{
    public const STATUSES = ['Open', 'In Progress', 'Escalated', 'Resolved'];

    protected $table = 'routine_complaints';

    protected $fillable = [
        'reference', 'household_id', 'placement_id', 'reported_by', 'assigned_to', 'subject', 'details',
        'status', 'resolution', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['subject' => 'encrypted', 'details' => 'encrypted', 'resolution' => 'encrypted', 'resolved_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (RoutineComplaint $complaint): void {
            if ($complaint->status !== 'Open') {
                throw new \LogicException('A routine complaint must start in Open status.');
            }
        });
        static::updating(function (RoutineComplaint $complaint): void {
            $immutable = ['reference', 'household_id', 'placement_id', 'reported_by', 'subject', 'details'];
            if (array_intersect(array_keys($complaint->getDirty()), $immutable) !== []) {
                throw new \LogicException('Complaint intake facts are immutable; record a new complaint for a separate issue.');
            }

            $allowed = [
                'Open' => ['In Progress', 'Escalated'],
                'In Progress' => ['Escalated', 'Resolved'],
                'Escalated' => ['In Progress', 'Resolved'],
            ];
            if ($complaint->isDirty('status') && ! in_array($complaint->status, $allowed[$complaint->getOriginal('status')] ?? [], true)) {
                throw new \LogicException('Invalid routine complaint status transition.');
            }
            if ($complaint->isDirty('resolution') || $complaint->isDirty(['resolved_by', 'resolved_at'])) {
                if ($complaint->status !== 'Resolved' || trim((string) $complaint->resolution) === '' || ! $complaint->resolved_by || ! $complaint->resolved_at) {
                    throw new \LogicException('A complaint resolution requires a resolved status, note, actor and timestamp.');
                }
            }
            if ($complaint->isDirty('status') && $complaint->status === 'Resolved'
                && (! $complaint->resolved_by || ! $complaint->resolved_at || trim((string) $complaint->resolution) === '')) {
                throw new \LogicException('Resolving a complaint requires a resolution note, actor and timestamp.');
            }
            if ($complaint->getOriginal('status') === 'Resolved' && $complaint->isDirty('assigned_to')) {
                throw new \LogicException('Resolved complaints cannot be reassigned.');
            }
            if ($complaint->getOriginal('status') === 'Resolved' && $complaint->isDirty(['resolution', 'resolved_by', 'resolved_at'])) {
                throw new \LogicException('A recorded complaint resolution is immutable.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Complaint and audit history cannot be deleted.'));
    }

    public function household(): BelongsTo { return $this->belongsTo(Household::class)->withTrashed(); }
    public function placement(): BelongsTo { return $this->belongsTo(Placement::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
    public function events(): HasMany { return $this->hasMany(RoutineComplaintEvent::class, 'complaint_id')->orderBy('occurred_at'); }
}
