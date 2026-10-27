<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;

use App\Admin\Models\PriceVersion;
use App\Admin\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        try {
            DB::transaction(function () use ($request, $service, $price): void {
                Service::query()->lockForUpdate()->findOrFail($service->id);
                $lockedPrice = PriceVersion::query()->lockForUpdate()->findOrFail($price->id);
                abort_unless((int) $lockedPrice->service_id === (int) $service->id, 404);
                abort_unless($lockedPrice->status === 'Draft', 422, 'Only Draft price versions can be approved.');

                $overlap = PriceVersion::query()
                    ->where('service_id', $service->id)
                    ->where('status', 'Approved')
                    ->where('is_active', true)
                    ->where('id', '!=', $lockedPrice->id)
                    ->where(function ($query) use ($lockedPrice): void {
                        $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $lockedPrice->effective_from);
                    })
                    ->when($lockedPrice->effective_to, fn ($query) => $query->whereDate('effective_from', '<=', $lockedPrice->effective_to))
                    ->exists();

                abort_unless(! $overlap, 422, 'An approved price version already covers this period. Retire it first.');

                $lockedPrice->forceFill([
                    'status' => 'Approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ])->save();
                activity()->causedBy($request->user())->performedOn($lockedPrice)->log('Price version approved');
            });
            flash()->success('Price version approved.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function retire(Request $request, Service $service, PriceVersion $price)
    {
        try {
            DB::transaction(function () use ($request, $service, $price): void {
                Service::query()->lockForUpdate()->findOrFail($service->id);
                $lockedPrice = PriceVersion::query()->lockForUpdate()->findOrFail($price->id);
                abort_unless((int) $lockedPrice->service_id === (int) $service->id, 404);
                abort_unless($lockedPrice->status === 'Approved', 422, 'Only approved price versions can be retired.');
                $lockedPrice->status = 'Retired';
                $lockedPrice->save();
                activity()->causedBy($request->user())->performedOn($lockedPrice)->log('Price version retired');
            });
            flash()->success('Price version retired.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function destroy(Request $request, Service $service, PriceVersion $price)
    {
        try {
            DB::transaction(function () use ($request, $service, $price): void {
                $lockedPrice = PriceVersion::query()->lockForUpdate()->findOrFail($price->id);
                abort_unless((int) $lockedPrice->service_id === (int) $service->id, 404);
                abort_unless($lockedPrice->status === 'Draft', 422, 'Only draft price versions may be deleted.');
                activity()->causedBy($request->user())->performedOn($lockedPrice)->log('Price version deleted');
                $lockedPrice->delete();
            });
            flash()->success('Price version deleted.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            flash()->error('Failed to delete price version.');
        }

        return back();
    }
}
