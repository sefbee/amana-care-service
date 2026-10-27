<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancePaymentAllocation extends Model
{
    protected $table = 'finance_payment_allocations';
    protected $fillable = ['payment_id', 'invoice_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Payment allocations are immutable.'));
        static::deleting(fn () => throw new \LogicException('Payment allocations cannot be deleted.'));
    }

    public function payment(): BelongsTo { return $this->belongsTo(FinancePayment::class, 'payment_id'); }
    public function invoice(): BelongsTo { return $this->belongsTo(FinanceInvoice::class, 'invoice_id'); }
}
