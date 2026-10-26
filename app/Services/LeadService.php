<?php

namespace App\Services;

use App\Models\Household;
use App\Models\HouseholdContact;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LeadService
{
    /**
     * Normalize a raw phone number to canonical Bangladesh format (01XXXXXXXXX)
     * or keep valid international format (+XXXXXXXXXXX).
     */
    public function normalizePhone(string $raw): string
    {
        // Strip common formatting characters
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $raw);

        // Strip +88 or 0088 country prefix to get local number
        if (str_starts_with($cleaned, '+880')) {
            $cleaned = '0' . substr($cleaned, 4);
        } elseif (str_starts_with($cleaned, '0088')) {
            $cleaned = '0' . substr($cleaned, 4);
        }

        // Validate Bangladesh mobile: 01[3-9]XXXXXXXX (11 digits)
        if (preg_match('/^01[3-9][0-9]{8}$/', $cleaned)) {
            return $cleaned;
        }

        // Validate international E.164: + followed by 7-15 digits
        $originalCleaned = preg_replace('/[\s\-\(\)]/', '', $raw);
        if (preg_match('/^\+[1-9][0-9]{6,14}$/', $originalCleaned)) {
            return $originalCleaned;
        }

        throw ValidationException::withMessages([
            'phone' => ['Invalid phone number. Use Bangladesh mobile format (01XXXXXXXXX) or international format (+XXXXXXXXXXX).'],
        ]);
    }

    /**
     * Return the allowed status transitions map.
     */
    public function getAllowedTransitions(): array
    {
        return [
            'New'          => ['Contacted', 'Lost', 'Not Eligible'],
            'Contacted'    => ['Qualified', 'Lost', 'Not Eligible'],
            'Qualified'    => ['Converted', 'Waitlisted', 'Lost', 'Not Eligible'],
            'Converted'    => [],
            'Waitlisted'   => ['Qualified', 'Lost', 'Not Eligible'],
            'Lost'         => [],
            'Not Eligible' => [],
        ];
    }

    /**
     * Validate a status transition. Returns true if allowed.
     */
    public function validateTransition(string $from, string $to): bool
    {
        $allowed = $this->getAllowedTransitions();

        return isset($allowed[$from]) && in_array($to, $allowed[$from]);
    }

    /**
     * Record a status change to lead_status_histories.
     */
    public function recordStatusHistory(Lead $lead, string $from, string $to): void
    {
        LeadStatusHistory::create([
            'lead_id'    => $lead->id,
            'from_status' => $from,
            'to_status'  => $to,
            'changed_by' => auth()->id(),
        ]);
    }

    /**
     * Detect duplicate leads/households by normalized phone or email.
     * Returns a collection of matching records.
     */
    public function detectDuplicates(string $normalizedPhone, ?string $email, ?int $excludeId = null): Collection
    {
        $duplicates = collect();

        // Check leads by phone
        $leadsByPhone = Lead::query()
            ->where('normalized_phone', $normalizedPhone)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get(['id', 'full_name', 'status', 'created_at'])
            ->map(fn ($l) => array_merge($l->toArray(), ['type' => 'Lead']));

        $duplicates = $duplicates->merge($leadsByPhone);

        // Check leads by email
        if ($email) {
            $leadsByEmail = Lead::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->whereNotIn('id', $leadsByPhone->pluck('id'))
                ->get(['id', 'full_name', 'status', 'created_at'])
                ->map(fn ($l) => array_merge($l->toArray(), ['type' => 'Lead']));

            $duplicates = $duplicates->merge($leadsByEmail);
        }

        // Check household contacts by phone
        $householdsByPhone = HouseholdContact::query()
            ->where('phone', $normalizedPhone)
            ->with('household:id,display_name,status,created_at')
            ->get()
            ->map(fn ($c) => [
                'id'         => $c->household->id ?? null,
                'full_name'  => $c->household->display_name ?? null,
                'status'     => $c->household->status ?? null,
                'created_at' => $c->household->created_at ?? null,
                'type'       => 'Household',
            ])
            ->filter(fn ($h) => $h['id'] !== null);

        $duplicates = $duplicates->merge($householdsByPhone);

        // Check household contacts by email
        if ($email) {
            $householdsByEmail = HouseholdContact::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->with('household:id,display_name,status,created_at')
                ->get()
                ->map(fn ($c) => [
                    'id'         => $c->household->id ?? null,
                    'full_name'  => $c->household->display_name ?? null,
                    'status'     => $c->household->status ?? null,
                    'created_at' => $c->household->created_at ?? null,
                    'type'       => 'Household',
                ])
                ->filter(fn ($h) => $h['id'] !== null);

            $duplicates = $duplicates->merge($householdsByEmail);
        }

        return $duplicates->values();
    }

    /**
     * Roll up consent_status on the Lead based on its consent records.
     */
    public function rollUpConsentStatus(Lead $lead): void
    {
        $records = $lead->consentRecords()->get(['status']);

        if ($records->where('status', 'Granted')->isNotEmpty()) {
            $lead->consent_status = 'Granted';
        } elseif ($records->where('status', 'Withdrawn')->isNotEmpty()) {
            $lead->consent_status = 'Withdrawn';
        } else {
            $lead->consent_status = 'Unknown';
        }

        $lead->save();
    }
}
