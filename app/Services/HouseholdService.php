<?php

namespace App\Services;

use App\Models\Household;
use App\Models\HouseholdContact;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HouseholdService
{
    public function __construct(protected LeadService $leadService) {}

    /**
     * Convert a Qualified Lead into a Household inside a transaction.
     */
    public function convertFromLead(Lead $lead, array $extraData = []): Household
    {
        return DB::transaction(function () use ($lead, $extraData) {
            $household = Household::create(array_merge([
                'display_name'            => $lead->full_name,
                'area'                    => $lead->area ?? '',
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
                'email'        => $lead->email,
                'is_active'    => true,
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
            ->where('role', 'Primary')
            ->where('id', '!=', $contactId)
            ->count();

        if ($remainingPrimary === 0) {
            throw ValidationException::withMessages([
                'role' => ['Cannot delete the last Primary contact. Assign another Primary contact first.'],
            ]);
        }
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
