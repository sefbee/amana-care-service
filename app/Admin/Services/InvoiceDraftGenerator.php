<?php

namespace App\Admin\Services;

use App\Admin\Models\FinanceInvoice;
use App\Admin\Models\FinanceInvoiceLine;
use App\Admin\Models\HouseholdContact;
use App\Admin\Models\Placement;
use App\Admin\Models\PriceVersion;
use App\Admin\Models\QuoteLine;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class InvoiceDraftGenerator
{
    /** @return array{created:int, existing:int, skipped:array<string,int>, period_start:string|null, period_end:string|null} */
    public function generateUpcoming(): array
    {
        if (config('finance.billing_cycle') !== 'monthly_in_advance') {
            return ['created' => 0, 'existing' => 0, 'skipped' => ['billing_cycle_not_approved' => 1], 'period_start' => null, 'period_end' => null];
        }
        if (config('finance.recurring_amount_basis') !== 'placement_unit_price') {
            return ['created' => 0, 'existing' => 0, 'skipped' => ['recurring_amount_basis_not_approved' => 1], 'period_start' => null, 'period_end' => null];
        }

        $periodStart = CarbonImmutable::today(config('app.timezone'))->startOfMonth()->addMonth();
        $periodEnd = $periodStart->endOfMonth();
        $summary = ['created' => 0, 'existing' => 0, 'skipped' => [], 'period_start' => $periodStart->toDateString(), 'period_end' => $periodEnd->toDateString()];

        Placement::query()->where('status', 'Active')
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $periodStart->toDateString()))
            ->orderBy('id')->chunkById(100, function ($placements) use ($periodStart, $periodEnd, &$summary): void {
                foreach ($placements as $placement) {
                    [$created, $reason] = $this->createDraftForPeriod($placement->id, $periodStart, $periodEnd);
                    if ($created) {
                        $summary['created']++;
                    } elseif ($reason === 'already_exists') {
                        $summary['existing']++;
                    } else {
                        $summary['skipped'][$reason] = ($summary['skipped'][$reason] ?? 0) + 1;
                    }
                }
            });

        return $summary;
    }

    /** @return array{bool,string|null} */
    public function createDraftForPeriod(int $placementId, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        return DB::transaction(function () use ($placementId, $periodStart, $periodEnd): array {
            $placement = Placement::query()->lockForUpdate()->find($placementId);
            if (! $placement || $placement->status !== 'Active') {
                return [false, 'placement_not_active'];
            }

            if (FinanceInvoice::query()->where('placement_id', $placement->id)
                ->whereDate('period_start', $periodStart->toDateString())
                ->whereDate('period_end', $periodEnd->toDateString())->exists()) {
                return [false, 'already_exists'];
            }

            $quoteLine = QuoteLine::query()->where('quote_id', $placement->quote_id)
                ->where('price_version_id', $placement->price_version_id)
                ->first();
            if (! $quoteLine) {
                return [false, 'price_snapshot_missing'];
            }

            $unit = mb_strtolower(trim($quoteLine->unit_snapshot));
            if (str_contains($unit, 'month')) {
                if ($periodStart->day !== 1 || $periodEnd->toDateString() !== $periodStart->endOfMonth()->toDateString()) {
                    return [false, 'monthly_period_must_be_full_calendar_month'];
                }
                if ($placement->start_date->gt($periodStart) || ($placement->end_date && $placement->end_date->lt($periodEnd))) {
                    return [false, 'partial_month_billing_policy_unresolved'];
                }
                $quantity = 1;
            } elseif (str_contains($unit, 'day')) {
                $billStart = $placement->start_date->greaterThan($periodStart) ? CarbonImmutable::parse($placement->start_date) : $periodStart;
                $billEnd = $placement->end_date && $placement->end_date->lessThan($periodEnd) ? CarbonImmutable::parse($placement->end_date) : $periodEnd;
                if ($billEnd->lt($billStart)) {
                    return [false, 'placement_outside_billing_period'];
                }
                $quantity = $billStart->diffInDays($billEnd) + 1;
            } else {
                return [false, 'unsupported_price_unit'];
            }

            $billingContacts = HouseholdContact::query()->where('household_id', $placement->household_id)
                ->where('is_active', true)->where('is_billing_contact', true)->get();
            if ($billingContacts->count() !== 1) {
                return [false, 'billing_contact_invariant_not_met'];
            }
            $billingContact = $billingContacts->first();

            $unitAmountCents = FinanceMoney::cents((string) $placement->getRawOriginal('price_amount_snapshot'));
            $lineTotalCents = $unitAmountCents * $quantity;
            if ($lineTotalCents > 999999999999) {
                return [false, 'invoice_total_exceeds_supported_precision'];
            }

            $sequence = DB::table('finance_invoice_sequences')->where('id', 1)->lockForUpdate()->first();
            if (! $sequence) {
                throw new \RuntimeException('Finance invoice sequence row is missing.');
            }
            $sequenceNumber = (int) $sequence->last_number + 1;
            DB::table('finance_invoice_sequences')->where('id', 1)->update(['last_number' => $sequenceNumber, 'updated_at' => now()]);
            $invoiceNumber = 'INV-'.str_pad((string) $sequenceNumber, 9, '0', STR_PAD_LEFT);
            $amount = FinanceMoney::decimal($lineTotalCents);

            $invoice = FinanceInvoice::query()->create([
                'sequence_number' => $sequenceNumber,
                'invoice_number' => $invoiceNumber,
                'placement_id' => $placement->id,
                'household_id' => $placement->household_id,
                'billing_contact_id' => $billingContact->id,
                'billing_contact_name' => $billingContact->name,
                'billing_contact_email' => $billingContact->email,
                'billing_contact_phone' => $billingContact->phone,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'currency' => $placement->currency,
                'subtotal' => $amount,
                'total' => $amount,
                'status' => 'Draft',
            ]);
            FinanceInvoiceLine::query()->create([
                'invoice_id' => $invoice->id,
                'price_version_id' => $placement->price_version_id,
                'description_snapshot' => $quoteLine->description_snapshot,
                'quantity' => $quantity,
                'unit_snapshot' => $quoteLine->unit_snapshot,
                'unit_amount' => FinanceMoney::decimal($unitAmountCents),
                'line_total' => $amount,
            ]);

            activity('finance_invoice')->performedOn($invoice)
                ->withProperties([
                    'placement_id' => $placement->id,
                    'price_version_id' => $placement->price_version_id,
                    'sequence_number' => $sequenceNumber,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'amount' => $amount,
                    'currency' => $placement->currency,
                ])->log('Draft invoice generated from immutable placement price snapshot');

            return [true, null];
        });
    }
}
