<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceInvoice extends Model
{
    protected $table = 'finance_invoices';

    protected $fillable = [
        'sequence_number', 'invoice_number', 'placement_id', 'household_id', 'billing_contact_id',
        'billing_contact_name', 'billing_contact_email', 'billing_contact_phone', 'period_start',
        'period_end', 'currency', 'subtotal', 'total', 'status', 'issued_by', 'issued_at', 'due_date',
    ];

    protected function casts(): array
    {
        return ['period_start' => 'date:Y-m-d', 'period_end' => 'date:Y-m-d', 'issued_at' => 'datetime', 'due_date' => 'date:Y-m-d', 'subtotal' => 'decimal:2', 'total' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::updating(function (FinanceInvoice $invoice): void {
            $immutable = ['sequence_number', 'invoice_number', 'placement_id', 'household_id', 'billing_contact_id', 'billing_contact_name', 'billing_contact_email', 'billing_contact_phone', 'period_start', 'period_end', 'currency', 'subtotal', 'total'];
            if (array_intersect(array_keys($invoice->getDirty()), $immutable) !== []) {
                throw new \LogicException('Invoice identity, recipient and amount snapshots are immutable.');
            }
            $allowed = [
                'Draft' => ['Issued'],
                'Issued' => ['Partially Paid', 'Paid', 'Overdue'],
                'Partially Paid' => ['Paid', 'Overdue'],
                'Overdue' => ['Partially Paid', 'Paid'],
            ];
            if ($invoice->isDirty('status') && ! in_array($invoice->status, $allowed[$invoice->getOriginal('status')] ?? [], true)) {
                throw new \LogicException('Invalid invoice state transition.');
            }
            $issuedFieldsChanged = $invoice->isDirty(['issued_by', 'issued_at', 'due_date']);
            if ($invoice->getOriginal('status') !== 'Draft' && $issuedFieldsChanged) {
                throw new \LogicException('Issued invoice terms cannot be changed.');
            }
            if ($invoice->isDirty('status') && $invoice->status === 'Issued'
                && (! $invoice->issued_by || ! $invoice->issued_at || ! $invoice->due_date)) {
                throw new \LogicException('An issued invoice requires an issuer, issue time, and due date.');
            }
            if ($invoice->getOriginal('status') === 'Draft' && $issuedFieldsChanged && $invoice->status !== 'Issued') {
                throw new \LogicException('Issue terms can only be set while issuing a draft invoice.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Invoice financial history cannot be deleted.'));
    }

    public function placement(): BelongsTo { return $this->belongsTo(Placement::class); }
    public function household(): BelongsTo { return $this->belongsTo(Household::class); }
    public function billingContact(): BelongsTo { return $this->belongsTo(HouseholdContact::class, 'billing_contact_id')->withTrashed(); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    public function lines(): HasMany { return $this->hasMany(FinanceInvoiceLine::class, 'invoice_id'); }
    public function allocations(): HasMany { return $this->hasMany(FinancePaymentAllocation::class, 'invoice_id'); }
    public function payments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(FinancePayment::class, 'finance_payment_allocations', 'invoice_id', 'payment_id')->withPivot('amount');
    }
    public function getPaidAmountAttribute(): string
    {
        $cents = 0;
        foreach ($this->allocations()->pluck('amount') as $amount) {
            $cents += \App\Admin\Services\FinanceMoney::cents((string) $amount);
        }
        return \App\Admin\Services\FinanceMoney::decimal($cents);
    }
    public function getBalanceAttribute(): string
    {
        $total = \App\Admin\Services\FinanceMoney::cents($this->getRawOriginal('total'));
        $paid = \App\Admin\Services\FinanceMoney::cents($this->paid_amount);
        return \App\Admin\Services\FinanceMoney::decimal(max(0, $total - $paid));
    }
}
