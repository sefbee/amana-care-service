<?php

namespace App\Models;

use App\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BuildsQueries;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'full_name', 'phone', 'normalized_phone', 'email', 'channel',
        'service_interest', 'area', 'live_in_pref', 'preferred_start_date',
        'budget_expectation', 'notes', 'status', 'consent_status',
        'lost_reason', 'lost_reason_notes', 'waitlist_priority',
        'override_reason', 'household_id', 'owner_id', 'is_active', 'created_by',
    ];

    protected $hidden = ['deleted_at'];

    protected $appends = ['status_badge'];

    protected function casts(): array
    {
        return [
            'preferred_start_date' => 'date:Y-m-d',
            'budget_expectation'   => 'decimal:2',
            'is_active'            => 'boolean',
        ];
    }

    public function getStatusBadgeAttribute(): string
    {
        $map = [
            'New'          => 'primary',
            'Contacted'    => 'info',
            'Qualified'    => 'success',
            'Converted'    => 'dark',
            'Waitlisted'   => 'warning',
            'Lost'         => 'danger',
            'Not Eligible' => 'secondary',
        ];
        $color = $map[$this->status] ?? 'secondary';

        return "<span class='badge badge-light-{$color}'>{$this->status}</span>";
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(LeadCommunicationLog::class)->latest('contact_date');
    }

    public function consentRecords(): HasMany
    {
        return $this->hasMany(LeadConsentRecord::class)->latest();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class)->latest();
    }
}
