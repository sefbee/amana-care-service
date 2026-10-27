<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverDocument extends Model
{
    protected $fillable = [
        'caregiver_id', 'type', 'storage_disk', 'storage_key', 'original_name',
        'mime_type', 'size_bytes', 'expiry_date', 'scan_status', 'scan_result',
        'uploaded_by', 'scanned_at',
    ];

    protected $hidden = ['storage_key'];

    protected function casts(): array
    {
        return ['expiry_date' => 'date:Y-m-d', 'scanned_at' => 'datetime'];
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }
}
