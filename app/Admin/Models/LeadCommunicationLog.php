<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadCommunicationLog extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'lead_id', 'contact_date', 'direction', 'channel',
        'summary', 'next_step', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'contact_date' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
