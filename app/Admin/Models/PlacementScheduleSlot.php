<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementScheduleSlot extends Model
{
    protected $fillable = ['placement_id', 'weekday', 'start_minute', 'end_minute'];
    public function placement(): BelongsTo { return $this->belongsTo(Placement::class); }
}
