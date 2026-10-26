<?php

namespace App\Http\Controllers\Household;

use App\Http\Controllers\Controller;
use App\Models\CareRecipient;
use App\Models\Household;
use App\Rules\NonClinical;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CareRecipientController extends Controller
{
    private function validateRecipient(Request $request): array
    {
        return $request->validate([
            'first_name'           => ['required', 'string', 'max:100'],
            'recipient_type'       => ['required', Rule::in(config('crm.recipient_types'))],
            'date_of_birth'        => ['nullable', 'date', 'before:today'],
            'age_band'             => ['nullable', Rule::in(config('crm.age_bands'))],
            'relationship'         => ['nullable', 'string', 'max:100'],
            'support_notes'        => ['nullable', 'string', 'max:1000', new NonClinical],
            'routines'             => ['nullable', 'string', 'max:1000', new NonClinical],
            'dietary_restrictions' => ['nullable', 'string', 'max:500'],
        ]);
    }

    public function store(Request $request, Household $household)
    {
        $validated = $this->validateRecipient($request);

        if (empty($validated['date_of_birth']) && empty($validated['age_band'])) {
            return back()->withErrors([
                'date_of_birth' => 'Either date of birth or age band is required.',
            ])->withInput();
        }

        try {
            $validated['household_id'] = $household->id;
            $validated['created_by']   = auth()->id();
            $validated['is_sensitive'] = ($validated['recipient_type'] === 'Child');

            $recipient = CareRecipient::create($validated);
            activity()->causedBy(auth()->user())->performedOn($recipient)->log('Care recipient added');
            flash()->success('Care recipient added.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function update(Request $request, Household $household, CareRecipient $recipient)
    {
        $validated = $this->validateRecipient($request);

        if (empty($validated['date_of_birth']) && empty($validated['age_band'])) {
            return back()->withErrors([
                'date_of_birth' => 'Either date of birth or age band is required.',
            ])->withInput();
        }

        try {
            // Enforce Child always sensitive
            $validated['is_sensitive'] = ($validated['recipient_type'] === 'Child') ? true : $recipient->is_sensitive;

            $recipient->update($validated);
            activity()->causedBy(auth()->user())->performedOn($recipient)->log('Care recipient updated');
            flash()->success('Care recipient updated.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function destroy(Household $household, CareRecipient $recipient)
    {
        try {
            activity()->causedBy(auth()->user())->performedOn($recipient)->log('Care recipient deleted');
            $recipient->delete();
            flash()->success('Care recipient deleted.');
        } catch (\Exception $e) {
            flash()->error('Failed to delete care recipient.');
        }

        return back();
    }
}
