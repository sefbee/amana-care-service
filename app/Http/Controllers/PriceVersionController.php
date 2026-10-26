<?php

namespace App\Http\Controllers;

use App\Models\PriceVersion;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PriceVersionController extends Controller
{
    public function store(Request $request, Service $service)
    {
        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'unit'           => ['required', 'string', 'max:50'],
            'price_type'     => ['required', Rule::in(['Indicative Starting Price', 'Fixed'])],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after:effective_from'],
            'source_note'    => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $validated['service_id']  = $service->id;
            $validated['created_by']  = auth()->id();
            $validated['status']      = 'Draft';

            $price = PriceVersion::create($validated);
            activity()->causedBy(auth()->user())->performedOn($price)->log('Price version created');
            flash()->success('Price version added.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function approve(Request $request, Service $service, PriceVersion $price)
    {
        // BR-015: approver must differ from creator
        if ($price->created_by === auth()->id()) {
            flash()->error('You cannot approve a price version you created (segregation of duties).');
            return back();
        }

        if ($price->status !== 'Draft') {
            flash()->error('Only Draft price versions can be approved.');
            return back();
        }

        // Check for overlapping approved periods for the same service
        $overlap = PriceVersion::where('service_id', $service->id)
            ->where('status', 'Approved')
            ->where('id', '!=', $price->id)
            ->where(function ($q) use ($price) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $price->effective_from);
            })
            ->when($price->effective_to, function ($q) use ($price) {
                $q->where('effective_from', '<=', $price->effective_to);
            })
            ->exists();

        if ($overlap) {
            flash()->error('An approved price version already covers this period. Retire it first.');
            return back();
        }

        try {
            $price->update([
                'status'      => 'Approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            activity()->causedBy(auth()->user())->performedOn($price)->log('Price version approved');
            flash()->success('Price version approved.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function retire(Service $service, PriceVersion $price)
    {
        if ($price->status === 'Retired') {
            flash()->error('Already retired.');
            return back();
        }

        try {
            $price->update(['status' => 'Retired']);
            activity()->causedBy(auth()->user())->performedOn($price)->log('Price version retired');
            flash()->success('Price version retired.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function destroy(Service $service, PriceVersion $price)
    {
        if ($price->status === 'Approved') {
            flash()->error('Approved price versions cannot be deleted. Retire it instead.');
            return back();
        }

        try {
            activity()->causedBy(auth()->user())->performedOn($price)->log('Price version deleted');
            $price->delete();
            flash()->success('Price version deleted.');
        } catch (\Exception $e) {
            flash()->error('Failed to delete price version.');
        }

        return back();
    }
}
