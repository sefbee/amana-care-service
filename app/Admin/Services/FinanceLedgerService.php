<?php

namespace App\Admin\Services;

use App\Admin\Models\FinanceInvoice;
use App\Admin\Models\FinancePayment;
use App\Admin\Models\FinancePaymentAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceLedgerService
{
    public function issue(FinanceInvoice $invoice, string $dueDate, int $actorId): void
    {
        DB::transaction(function () use ($invoice, $dueDate, $actorId): void {
            $locked = FinanceInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            abort_unless($locked->status === 'Draft', 422, 'Only draft invoices can be issued.');
            abort_if(FinanceMoney::cents($locked->getRawOriginal('total')) <= 0, 422, 'Invoice total must be greater than zero.');

            $locked->forceFill([
                'status' => 'Issued',
                'issued_by' => $actorId,
                'issued_at' => now(),
                'due_date' => $dueDate,
            ])->save();
            activity('finance_invoice')->causedBy($actorId)->performedOn($locked)
                ->withProperties(['invoice_number' => $locked->invoice_number, 'due_date' => $dueDate, 'amount' => $locked->total])
                ->log('Draft invoice issued');
        });
    }

    /**
     * @param array<int,array{invoice_id:int,amount:string}> $allocations
     */
    public function recordPayment(array $data, int $actorId): FinancePayment
    {
        return DB::transaction(function () use ($data, $actorId): FinancePayment {
            $allocationRows = collect($data['allocations'])->map(fn (array $row): array => [
                'invoice_id' => (int) $row['invoice_id'],
                'amount_cents' => FinanceMoney::cents((string) $row['amount']),
            ])->values();
            abort_if($allocationRows->pluck('invoice_id')->unique()->count() !== $allocationRows->count(), 422, 'Each invoice can appear only once in a payment.');

            $invoiceIds = $allocationRows->pluck('invoice_id')->sort()->values()->all();
            $invoices = FinanceInvoice::query()->whereIn('id', $invoiceIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            abort_unless($invoices->count() === count($invoiceIds), 422, 'One or more invoices are unavailable.');
            $householdIds = $invoices->pluck('household_id')->unique();
            abort_unless($householdIds->count() === 1, 422, 'A payment can be allocated only to invoices for one household.');
            $totalCents = 0;
            foreach ($allocationRows as $row) {
                $invoice = $invoices->get($row['invoice_id']);
                abort_unless(in_array($invoice->status, ['Issued', 'Partially Paid', 'Overdue'], true), 422, 'Payments can only be allocated to issued invoices.');
                abort_if($row['amount_cents'] <= 0, 422, 'Every invoice allocation must be greater than zero.');
                $balanceCents = FinanceMoney::cents($invoice->balance);
                abort_if($row['amount_cents'] > $balanceCents, 422, 'A payment allocation cannot exceed the invoice balance.');
                $totalCents += $row['amount_cents'];
            }
            abort_if($totalCents > 999999999999, 422, 'Payment total exceeds the supported currency precision.');

            $payment = FinancePayment::query()->create([
                'receipt_number' => 'RCT-'.strtoupper((string) Str::ulid()),
                'household_id' => $householdIds->first(),
                'amount' => FinanceMoney::decimal($totalCents),
                'currency' => 'BDT',
                'method' => $data['method'],
                'reference_id' => trim($data['reference_id']),
                'paid_at' => $data['paid_at'],
                'recorded_by' => $actorId,
            ]);

            foreach ($allocationRows as $row) {
                FinancePaymentAllocation::query()->create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $row['invoice_id'],
                    'amount' => FinanceMoney::decimal($row['amount_cents']),
                ]);
            }

            foreach ($invoices as $invoice) {
                $this->refreshInvoicePaymentStatus($invoice->id);
            }
            activity('finance_payment')->causedBy($actorId)->performedOn($payment)
                ->withProperties([
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'method' => $payment->method,
                    'payee' => 'Amanah',
                    'invoice_ids' => $allocationRows->pluck('invoice_id')->all(),
                    'status' => 'Unreconciled',
                ])->log('Manual payment recorded and receipt created');

            return $payment;
        });
    }

    public function reconcile(FinancePayment $payment, string $statementReference, int $actorId): void
    {
        DB::transaction(function () use ($payment, $statementReference, $actorId): void {
            $locked = FinancePayment::query()->lockForUpdate()->findOrFail($payment->id);
            abort_unless($locked->status === 'Unreconciled', 422, 'Only unreconciled payments can be reconciled.');
            $locked->forceFill([
                'status' => 'Reconciled',
                'reconciled_by' => $actorId,
                'reconciled_at' => now(),
                'bank_statement_reference' => trim($statementReference),
            ])->save();
            activity('finance_payment_reconciliation')->causedBy($actorId)->performedOn($locked)
                ->withProperties(['receipt_number' => $locked->receipt_number, 'amount' => $locked->amount, 'result' => 'reconciled'])
                ->log('Payment matched to bank statement and locked');
        });
    }

    public function markOverdue(): int
    {
        $changed = 0;
        FinanceInvoice::query()->whereIn('status', ['Issued', 'Partially Paid'])
            ->whereNotNull('due_date')->whereDate('due_date', '<', today())
            ->orderBy('id')->chunkById(100, function ($invoices) use (&$changed): void {
                foreach ($invoices as $invoice) {
                    DB::transaction(function () use ($invoice, &$changed): void {
                        $locked = FinanceInvoice::query()->lockForUpdate()->find($invoice->id);
                        if (! $locked || ! in_array($locked->status, ['Issued', 'Partially Paid'], true) || ! $locked->due_date || $locked->due_date->gte(today())) {
                            return;
                        }
                        if (FinanceMoney::cents($locked->balance) <= 0) {
                            $this->refreshInvoicePaymentStatus($locked->id);
                            return;
                        }
                        $locked->forceFill(['status' => 'Overdue'])->save();
                        activity('finance_invoice_overdue')->performedOn($locked)
                            ->withProperties(['invoice_number' => $locked->invoice_number, 'due_date' => $locked->due_date->toDateString(), 'balance' => $locked->balance])
                            ->log('Issued invoice marked overdue');
                        $changed++;
                    });
                }
            });

        return $changed;
    }

    private function refreshInvoicePaymentStatus(int $invoiceId): void
    {
        $invoice = FinanceInvoice::query()->lockForUpdate()->findOrFail($invoiceId);
        if ($invoice->status === 'Draft') {
            return;
        }
        $balance = FinanceMoney::cents($invoice->balance);
        $total = FinanceMoney::cents($invoice->getRawOriginal('total'));
        $status = $balance === 0 ? 'Paid' : ($invoice->due_date && $invoice->due_date->lt(today()) ? 'Overdue' : ($balance < $total ? 'Partially Paid' : 'Issued'));
        if ($invoice->status === 'Overdue' && $balance > 0) {
            $status = 'Overdue';
        }
        if ($status !== $invoice->status) {
            $from = $invoice->status;
            $invoice->forceFill(['status' => $status])->save();
            activity('finance_invoice')->performedOn($invoice)
                ->withProperties(['invoice_number' => $invoice->invoice_number, 'from' => $from, 'to' => $status, 'balance' => $invoice->balance])
                ->log('Invoice payment status recalculated');
        }
    }
}
