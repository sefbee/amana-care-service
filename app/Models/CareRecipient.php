<?php

namespace App\Models;

use App\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareRecipient extends Model
{
    use BuildsQueries;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'household_id', 'first_name', 'recipient_type', 'date_of_birth',
        'age_band', 'relationship', 'support_notes', 'routines',
        'dietary_restrictions', 'is_sensitive', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'is_sensitive'  => 'boolean',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
