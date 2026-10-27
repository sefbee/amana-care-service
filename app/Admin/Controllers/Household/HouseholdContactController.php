<?php

namespace App\Admin\Controllers\Household;

use App\Http\Controllers\Controller;
use App\Admin\Models\Household;
use App\Admin\Models\HouseholdContact;
use App\Admin\Services\HouseholdService;
use App\Admin\Authorization\CrmRecordAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HouseholdContactController extends Controller
{
    public function __construct(protected HouseholdService $householdService, protected CrmRecordAccess $recordAccess) {}

    public function store(Request $request, Household $household)
    {
        $this->recordAccess->assertHousehold($request->user(), $household);
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'is_primary_decision_maker' => ['nullable', 'boolean'],
            'is_billing_contact' => ['nullable', 'boolean'],
            'is_emergency_contact' => ['nullable', 'boolean'],
            'phone' => ['required', 'regex:/^(01[3-9][0-9]{8}|\\+[1-9][0-9]{6,14})$/'],
            'email' => ['nullable', 'email', 'max:254'],
        ]);

        try {
            $roles = [
                'is_primary_decision_maker' => (bool) ($validated['is_primary_decision_maker'] ?? false),
                'is_billing_contact' => (bool) ($validated['is_billing_contact'] ?? false),
                'is_emergency_contact' => (bool) ($validated['is_emergency_contact'] ?? false),
            ];
            unset($validated['is_primary_decision_maker'], $validated['is_billing_contact'], $validated['is_emergency_contact']);
            $contact = $this->householdService->saveContact($household, $validated, $roles);

            activity()->causedBy(auth()->user())->performedOn($contact)->log('Household contact added');

            flash()->success('Contact added successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function update(Request $request, Household $household, HouseholdContact $contact)
    {
        $this->recordAccess->assertHousehold($request->user(), $household);
        abort_unless((int) $contact->household_id === (int) $household->id, 404);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'is_primary_decision_maker' => ['nullable', 'boolean'],
            'is_billing_contact' => ['nullable', 'boolean'],
            'is_emergency_contact' => ['nullable', 'boolean'],
            'phone' => ['required', 'regex:/^(01[3-9][0-9]{8}|\\+[1-9][0-9]{6,14})$/'],
            'email' => ['nullable', 'email', 'max:254'],
        ]);

        try {
            $roles = [
                'is_primary_decision_maker' => (bool) ($validated['is_primary_decision_maker'] ?? false),
                'is_billing_contact' => (bool) ($validated['is_billing_contact'] ?? false),
                'is_emergency_contact' => (bool) ($validated['is_emergency_contact'] ?? false),
            ];
            unset($validated['is_primary_decision_maker'], $validated['is_billing_contact'], $validated['is_emergency_contact']);
            $this->householdService->saveContact($household, $validated, $roles, $contact);

            activity()->causedBy(auth()->user())->performedOn($contact)->log('Household contact updated');

            flash()->success('Contact updated successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function destroy(Household $household, HouseholdContact $contact)
    {
        $this->recordAccess->assertHousehold(request()->user(), $household);
        abort_unless((int) $contact->household_id === (int) $household->id, 404);

        try {
            $this->householdService->deleteContact($household, $contact);
            activity()->causedBy(auth()->user())->performedOn($contact)->log('Household contact deleted');

            flash()->success('Contact deleted.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            flash()->error($e->errors()['role'][0] ?? 'Cannot delete this contact.');
        } catch (\Exception $e) {
            flash()->error('Failed to delete contact.');
        }

        return back();
    }
}
