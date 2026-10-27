<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingModule extends Model
{
    protected $fillable = ['code', 'title', 'is_mandatory', 'is_active'];

    protected function casts(): array
    {
        return ['is_mandatory' => 'boolean', 'is_active' => 'boolean'];
    }

    public function records(): HasMany
    {
        return $this->hasMany(CaregiverTrainingRecord::class);
    }
}
