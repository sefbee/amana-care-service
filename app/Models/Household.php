<?php

namespace App\Models;

use App\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Household extends Model
{
    use BuildsQueries;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'display_name', 'area', 'address_line', 'access_notes',
        'primary_language', 'status', 'founding_cohort',
        'assigned_coordinator_id', 'is_active', 'created_by',
    ];

    // address_line excluded from serialization by default
    protected $hidden = ['address_line', 'deleted_at'];

    protected $appends = ['status_badge'];

    protected function casts(): array
    {
        return [
            'founding_cohort' => 'boolean',
            'is_active'       => 'boolean',
        ];
    }

    public function getStatusBadgeAttribute(): string
    {
        $map = [
            'Onboarding' => 'warning',
            'Active'     => 'success',
            'On Hold'    => 'info',
            'Closed'     => 'danger',
        ];
        $color = $map[$this->status] ?? 'secondary';

        return "<span class='badge badge-light-{$color}'>{$this->status}</span>";
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_coordinator_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(HouseholdContact::class);
    }

    public function careRecipients(): HasMany
    {
        return $this->hasMany(CareRecipient::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
