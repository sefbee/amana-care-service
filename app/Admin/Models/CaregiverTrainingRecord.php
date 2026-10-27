<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverTrainingRecord extends Model
{
    protected $fillable = ['caregiver_id', 'training_module_id', 'outcome', 'attended_at', 'recorded_by'];

    protected function casts(): array
    {
        return ['attended_at' => 'date:Y-m-d'];
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }
}
