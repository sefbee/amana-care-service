<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\Quote;
use App\Admin\Models\ServiceRequest;
use App\Admin\Models\PriceVersion;
use App\Admin\Models\Service;
use App\Admin\Models\QuoteLine;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest, CrmRecordAccess $access)
    {
        $access->assertHousehold($request->user(), $serviceRequest->household);

        $quote = DB::transaction(function () use ($request, $serviceRequest): Quote {
            $lockedRequest = ServiceRequest::query()->lockForUpdate()->findOrFail($serviceRequest->id);
            $service = Service::query()->whereKey($lockedRequest->service_id)->where('is_active', true)->firstOrFail();
            $price = PriceVersion::query()
                ->where('service_id', $service->id)
                ->where('status', 'Approved')->where('is_active', true)
                ->whereDate('effective_from', '<=', today())
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', today()))
                ->orderByDesc('effective_from')->lockForUpdate()->first();

            if (! $price) {
                throw ValidationException::withMessages(['quote' => 'There is no approved price effective for this service today. Ask the Operations Manager to approve the catalog price first.']);
            }

            $version = ((int) $lockedRequest->quotes()->max('version')) + 1;
            $amount = $this->normalizeMoney((string) $price->getRawOriginal('amount'));
            $quote = Quote::query()->create([
                'service_request_id' => $lockedRequest->id,
                'version' => $version,
                'created_by' => $request->user()->id,
                'currency' => 'BDT',
                'subtotal' => $amount,
                'status' => 'Draft',
            ]);
            QuoteLine::query()->create([
                'quote_id' => $quote->id,
                'service_id' => $service->id,
                'price_version_id' => $price->id,
                'description_snapshot' => $service->name,
                'quantity' => '1.00',
                'unit_snapshot' => $price->unit,
                'unit_amount' => $amount,
                'line_total' => $amount,
            ]);

            activity('catalog_quote')->causedBy($request->user())->performedOn($quote)
                ->withProperties(['service_request_id' => $lockedRequest->id, 'price_version_id' => $price->id, 'amount' => $amount, 'currency' => 'BDT', 'version' => $version])
                ->log('Draft quote created from approved price version');

            return $quote;
        });

        return redirect()->route('households.show', $serviceRequest->household_id)->with('success', 'Draft quote created from the currently approved price.');
    }

    public function approve(Request $request, Quote $quote, CrmRecordAccess $access)
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['om', 'platform-superuser'], true), 403);
        $validated = $request->validate([
            'override_total' => ['nullable', 'numeric', 'min:0.01', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'override_reason' => ['nullable', 'string', 'max:500', 'required_with:override_total'],
        ]);

        $serviceRequest = $quote->serviceRequest()->with('household')->firstOrFail();
        $access->assertHousehold($request->user(), $serviceRequest->household);

        DB::transaction(function () use ($request, $quote, $validated): void {
            $locked = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            abort_unless($locked->status === 'Draft', 422, 'Only draft quotes can be approved. Create another version for a revised quote.');
            $override = array_key_exists('override_total', $validated) && $validated['override_total'] !== null;
            $total = $override
                ? $this->normalizeMoney((string) $validated['override_total'])
                : $this->normalizeMoney((string) $locked->getRawOriginal('subtotal'));
            $reason = $override ? trim((string) $validated['override_reason']) : null;
            $locked->forceFill([
                'status' => 'Approved', 'approved_by' => $request->user()->id,
                'approved_at' => now(), 'final_total' => $total, 'override_reason' => $reason,
            ])->save();

            activity($override ? 'catalog_quote_override' : 'catalog_quote_approval')
                ->causedBy($request->user())->performedOn($locked)
                ->withProperties(['subtotal' => $locked->subtotal, 'final_total' => $total, 'currency' => $locked->currency, 'override_reason' => $reason])
                ->log($override ? 'Quote approved with OM override' : 'Quote approved');
        });

        return redirect()->route('households.show', $serviceRequest->household_id)->with('success', 'Quote approved and locked as a financial snapshot.');
    }

    private function normalizeMoney(string $value): string
    {
        if (! preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/', trim($value), $matches)) {
            throw ValidationException::withMessages(['amount' => 'Amounts must fit BDT decimal precision of 12,2.']);
        }

        return ltrim($matches[1], '0') === '' ? '0.'.str_pad($matches[2] ?? '', 2, '0') : ltrim($matches[1], '0').'.'.str_pad($matches[2] ?? '', 2, '0');
    }
}
