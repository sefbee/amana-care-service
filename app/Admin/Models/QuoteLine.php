<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLine extends Model
{
    protected static function booted(): void
    {
        static::creating(function (QuoteLine $line): void {
            abort_unless(Quote::query()->whereKey($line->quote_id)->where('status', 'Draft')->exists(), 422, 'Only draft quotes accept lines.');
        });
        static::updating(function (QuoteLine $line): void {
            $originalQuoteId = $line->getOriginal('quote_id');
            abort_unless(
                Quote::query()->whereKey($line->quote_id)->where('status', 'Draft')->exists()
                    && Quote::query()->whereKey($originalQuoteId)->where('status', 'Draft')->exists(),
                422,
                'Approved quote lines are immutable.'
            );
        });
        static::deleting(function (QuoteLine $line): void {
            abort_unless(Quote::query()->whereKey($line->quote_id)->where('status', 'Draft')->exists(), 422, 'Approved quote lines cannot be deleted.');
        });
    }

    protected $fillable = [
        'quote_id', 'service_id', 'price_version_id', 'description_snapshot',
        'quantity', 'unit_snapshot', 'unit_amount', 'line_total',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_amount' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function priceVersion(): BelongsTo
    {
        return $this->belongsTo(PriceVersion::class);
    }
}
