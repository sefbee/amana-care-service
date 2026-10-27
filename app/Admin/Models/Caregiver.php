<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caregiver extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'dob', 'gender', 'phone', 'normalized_phone', 'national_id_hash',
        'national_id_fingerprint', 'live_in_preferred', 'available_from',
        'zone_preferences', 'recruitment_stage', 'record_source', 'created_by', 'updated_by',
    ];

    protected $hidden = ['national_id_hash', 'national_id_fingerprint', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'dob' => 'date:Y-m-d',
            'available_from' => 'date:Y-m-d',
            'live_in_preferred' => 'boolean',
            'zone_preferences' => 'array',
            'national_id_hash' => 'encrypted',
        ];
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CaregiverSkill::class);
    }

    public function stageHistory(): HasMany
    {
        return $this->hasMany(CaregiverStageHistory::class)->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaregiverDocument::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function screeningChecks(): HasMany
    {
        return $this->hasMany(CaregiverScreeningCheck::class)->latest('checked_at');
    }

    public function trainingRecords(): HasMany
    {
        return $this->hasMany(CaregiverTrainingRecord::class);
    }

    public function readinessApprovals(): HasMany
    {
        return $this->hasMany(CaregiverReadinessApproval::class)->latest('approved_at');
    }
}
