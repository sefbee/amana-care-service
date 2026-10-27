<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Admin\Models\User;

class CaregiverReadinessApproval extends Model
{
    protected $fillable = ['caregiver_id', 'approved', 'reason', 'approved_by', 'approved_at'];

    protected function casts(): array
    {
        return ['approved' => 'boolean', 'approved_at' => 'datetime'];
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
