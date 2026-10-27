<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancePayment extends Model
{
    protected $table = 'finance_payments';
    public const METHODS = ['Bank Transfer', 'bKash', 'Cash'];

    protected $fillable = [
        'receipt_number', 'household_id', 'amount', 'currency', 'payee', 'method', 'reference_id',
        'paid_at', 'status', 'recorded_by', 'reconciled_by', 'reconciled_at', 'bank_statement_reference',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'date:Y-m-d', 'reconciled_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (FinancePayment $payment): void {
            abort_unless(in_array($payment->method, self::METHODS, true), 422, 'Unsupported payment method.');
            abort_unless($payment->currency === 'BDT', 422, 'Finance payments must be recorded in BDT.');
            abort_if(\App\Admin\Services\FinanceMoney::cents((string) $payment->amount) <= 0, 422, 'Payment amount must be greater than zero.');
            $payment->payee = 'Amanah';
            $payment->status = 'Unreconciled';
        });
        static::updating(function (FinancePayment $payment): void {
            if ($payment->getOriginal('status') === 'Reconciled') {
                throw new \LogicException('Reconciled payments are immutable.');
            }
            $allowed = ['status', 'reconciled_by', 'reconciled_at', 'bank_statement_reference', 'updated_at'];
            if (array_diff(array_keys($payment->getDirty()), $allowed) !== []) {
                throw new \LogicException('Payment entry terms cannot be edited; use the approved correction workflow.');
            }
            if ($payment->isDirty('status') && ! ($payment->getOriginal('status') === 'Unreconciled' && $payment->status === 'Reconciled')) {
                throw new \LogicException('Invalid payment reconciliation transition.');
            }
            if ($payment->isDirty(['reconciled_by', 'reconciled_at', 'bank_statement_reference'])
                && ! ($payment->status === 'Reconciled' && $payment->reconciled_by && $payment->reconciled_at && trim((string) $payment->bank_statement_reference) !== '')) {
                throw new \LogicException('Reconciliation requires an actor, timestamp and bank-statement reference.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Payment and receipt history cannot be deleted.'));
    }

    public function household(): BelongsTo { return $this->belongsTo(Household::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function reconciler(): BelongsTo { return $this->belongsTo(User::class, 'reconciled_by'); }
    public function allocations(): HasMany { return $this->hasMany(FinancePaymentAllocation::class, 'payment_id'); }
}
