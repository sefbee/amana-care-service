<?php

namespace App\Http\Controllers\Household;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\HouseholdContact;
use App\Services\HouseholdService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HouseholdContactController extends Controller
{
    public function __construct(protected HouseholdService $householdService) {}

    public function store(Request $request, Household $household)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'role'  => ['required', Rule::in(config('crm.contact_roles'))],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:254'],
        ]);

        try {
            $validated['household_id'] = $household->id;
            $contact = HouseholdContact::create($validated);

            activity()->causedBy(auth()->user())->performedOn($contact)->log('Household contact added');

            flash()->success('Contact added successfully.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function update(Request $request, Household $household, HouseholdContact $contact)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'role'  => ['required', Rule::in(config('crm.contact_roles'))],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:254'],
        ]);

        try {
            $contact->update($validated);

            activity()->causedBy(auth()->user())->performedOn($contact)->log('Household contact updated');

            flash()->success('Contact updated successfully.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function destroy(Household $household, HouseholdContact $contact)
    {
        try {
            $this->householdService->guardLastPrimaryContact($household, $contact->id);

            activity()->causedBy(auth()->user())->performedOn($contact)->log('Household contact deleted');
            $contact->delete();

            flash()->success('Contact deleted.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            flash()->error($e->errors()['role'][0] ?? 'Cannot delete this contact.');
        } catch (\Exception $e) {
            flash()->error('Failed to delete contact.');
        }

        return back();
    }
}
