<?php

namespace App\Models;

use App\Concerns\BuildsQueries;
use App\Traits\CommonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{

    use BuildsQueries, CommonAttributes {
        BuildsQueries::scopeActive insteadof CommonAttributes;
    }
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description', 'variant', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function priceVersions(): HasMany
    {
        return $this->hasMany(PriceVersion::class);
    }

    public function approvedPrices(): HasMany
    {
        return $this->hasMany(PriceVersion::class)->where('status', 'Approved')->orderByDesc('effective_from');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the current active approved price version.
     */
    public function currentPrice(): ?PriceVersion
    {
        return $this->approvedPrices()
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->first();
    }
}
