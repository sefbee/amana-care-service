<?php

namespace App\Admin\Services;

use App\Admin\Models\Household;
use App\Admin\Models\HouseholdContact;
use App\Admin\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HouseholdService
{
    public function __construct(protected LeadService $leadService) {}

    public function normalizeContactPhone(string $phone): string
    {
        return $this->leadService->normalizePhone($phone);
    }

    /**
     * Convert a Qualified Lead into a Household inside a transaction.
     */
    public function convertFromLead(Lead $lead, array $extraData = []): Household
    {
        if (! in_array($lead->area, config('crm.dhaka_zones', []), true)) {
            throw ValidationException::withMessages([
                'area' => ['Select a valid Dhaka zone before converting this lead to a Household.'],
            ]);
        }

        return DB::transaction(function () use ($lead, $extraData) {
            $household = Household::create(array_merge([
                'display_name'            => $lead->full_name,
                'area'                    => $lead->area,
                'primary_language'        => 'English',
                'status'                  => 'Onboarding',
                'assigned_coordinator_id' => $lead->owner_id ?? auth()->id(),
                'is_active'               => true,
                'created_by'              => auth()->id(),
            ], $extraData));

            HouseholdContact::create([
                'household_id' => $household->id,
                'name'         => $lead->full_name,
                'role'         => 'Primary',
                'phone'        => $lead->phone,
                'normalized_phone' => $lead->normalized_phone ?: $this->leadService->normalizePhone($lead->phone),
                'email'        => $lead->email,
                'is_active'    => true,
                'is_primary_decision_maker' => true,
                'is_billing_contact' => true,
            ]);

            $fromStatus = $lead->status;

            $lead->household_id = $household->id;
            $lead->status       = 'Converted';
            $lead->save();

            $this->leadService->recordStatusHistory($lead, $fromStatus, 'Converted');

            activity()
                ->causedBy(auth()->user())
                ->performedOn($lead)
                ->withProperties(['household_id' => $household->id])
                ->log('Lead converted to Household');

            return $household;
        });
    }

    /**
     * Guard against deleting the last Primary contact of a Household.
     */
    public function guardLastPrimaryContact(Household $household, int $contactId): void
    {
        $remainingPrimary = $household->contacts()
            ->where('is_primary_decision_maker', true)
            ->where('is_active', true)
            ->where('id', '!=', $contactId)
            ->count();

        if ($remainingPrimary === 0) {
            throw ValidationException::withMessages([
                'role' => ['Cannot remove the last Primary Decision Maker. Assign another first.'],
            ]);
        }

        $remainingBilling = $household->contacts()
            ->where('is_billing_contact', true)
            ->where('is_active', true)
            ->where('id', '!=', $contactId)
            ->count();
        if ($remainingBilling === 0) {
            throw ValidationException::withMessages([
                'role' => ['Cannot remove the last Billing Contact. Assign another first.'],
            ]);
        }
    }

    /**
     * Apply contact responsibilities atomically while preserving the required
     * single Primary Decision Maker and single Billing Contact invariants.
     */
    public function saveContact(Household $household, array $attributes, array $roles, ?HouseholdContact $contact = null): HouseholdContact
    {
        $attributes['normalized_phone'] = $this->leadService->normalizePhone($attributes['phone']);

        return DB::transaction(function () use ($household, $attributes, $roles, $contact): HouseholdContact {
            $lockedHousehold = Household::query()->lockForUpdate()->findOrFail($household->id);
            $contact ??= new HouseholdContact;
            $contact->household_id = $lockedHousehold->id;

            $flags = [
                'is_primary_decision_maker' => (bool) ($roles['is_primary_decision_maker'] ?? false),
                'is_billing_contact' => (bool) ($roles['is_billing_contact'] ?? false),
                'is_emergency_contact' => (bool) ($roles['is_emergency_contact'] ?? false),
            ];
            if (! in_array(true, $flags, true)) {
                throw ValidationException::withMessages(['roles' => ['Select at least one contact responsibility.']]);
            }

            foreach (['is_primary_decision_maker', 'is_billing_contact'] as $uniqueRole) {
                if ($flags[$uniqueRole]) {
                    $lockedHousehold->contacts()
                        ->where('id', '!=', $contact->id ?? 0)
                        ->update([$uniqueRole => false]);
                }
            }

            $contact->fill($attributes + $flags + [
                'role' => $flags['is_primary_decision_maker'] ? 'Primary' : ($flags['is_billing_contact'] ? 'Billing' : 'Emergency'),
                'is_active' => true,
            ]);
            $contact->save();

            $hasPrimary = $lockedHousehold->contacts()->where('is_active', true)->where('is_primary_decision_maker', true)->exists();
            $hasBilling = $lockedHousehold->contacts()->where('is_active', true)->where('is_billing_contact', true)->exists();
            if (! $hasPrimary || ! $hasBilling) {
                throw ValidationException::withMessages([
                    'roles' => ['Every Household must retain one Primary Decision Maker and one Billing Contact.'],
                ]);
            }

            return $contact;
        });
    }

    public function deleteContact(Household $household, HouseholdContact $contact): void
    {
        DB::transaction(function () use ($household, $contact): void {
            Household::query()->lockForUpdate()->findOrFail($household->id);
            $this->guardLastPrimaryContact($household, $contact->id);
            $contact->delete();
        });
    }

    /**
     * Cascade soft-delete all contacts and care recipients of a Household.
     */
    public function cascadeSoftDelete(Household $household): void
    {
        DB::transaction(function () use ($household) {
            $household->contacts()->each(fn ($c) => $c->delete());
            $household->careRecipients()->each(fn ($r) => $r->delete());
            $household->delete();
        });
    }
}
