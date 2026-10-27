<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequest extends Model
{
    protected $fillable = [
        'household_id', 'service_id', 'assigned_coordinator_id', 'created_by',
        'live_in_out', 'schedule_json', 'assessment_json', 'status',
    ];

    protected function casts(): array
    {
        return ['schedule_json' => 'array', 'assessment_json' => 'array'];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_coordinator_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->orderByDesc('version');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(MatchProposal::class)->latest();
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class)->latest('activated_at');
    }
}
