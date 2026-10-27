<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceInvoiceLine extends Model
{
    protected $table = 'finance_invoice_lines';
    protected $fillable = ['invoice_id', 'price_version_id', 'description_snapshot', 'quantity', 'unit_snapshot', 'unit_amount', 'line_total'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_amount' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Invoice line snapshots are immutable.'));
        static::deleting(fn () => throw new \LogicException('Invoice line snapshots cannot be deleted.'));
    }

    public function invoice(): BelongsTo { return $this->belongsTo(FinanceInvoice::class, 'invoice_id'); }
    public function priceVersion(): BelongsTo { return $this->belongsTo(PriceVersion::class); }
}
