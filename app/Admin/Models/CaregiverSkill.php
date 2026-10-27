<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverSkill extends Model
{
    protected $fillable = ['caregiver_id', 'skill_key', 'is_proficient', 'recorded_by'];

    protected function casts(): array
    {
        return ['is_proficient' => 'boolean'];
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }
}
