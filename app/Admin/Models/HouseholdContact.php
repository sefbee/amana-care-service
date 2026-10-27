<?php

namespace App\Admin\Models;

use App\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseholdContact extends Model
{
    use BuildsQueries;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'household_id', 'name', 'role', 'phone', 'normalized_phone', 'email', 'is_active',
        'is_primary_decision_maker', 'is_billing_contact', 'is_emergency_contact',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_primary_decision_maker' => 'boolean',
            'is_billing_contact' => 'boolean',
            'is_emergency_contact' => 'boolean',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
