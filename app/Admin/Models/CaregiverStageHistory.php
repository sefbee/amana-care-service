<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverStageHistory extends Model
{
    protected $fillable = ['caregiver_id', 'from_stage', 'to_stage', 'changed_by'];

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }
}
