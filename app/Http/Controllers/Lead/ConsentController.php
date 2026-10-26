<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadConsentRecord;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsentController extends Controller
{
    public function __construct(protected LeadService $leadService) {}

    public function store(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::in(config('crm.lead_channels'))],
            'status'  => ['required', Rule::in(['Unknown', 'Granted', 'Withdrawn'])],
            'source'  => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $validated['lead_id']    = $lead->id;
            $validated['created_by'] = auth()->id();

            if ($validated['status'] === 'Granted') {
                $validated['consented_at'] = now();
            } elseif ($validated['status'] === 'Withdrawn') {
                $validated['withdrawn_at'] = now();
            }

            $record = LeadConsentRecord::create($validated);

            // Roll up consent_status on the Lead
            $this->leadService->rollUpConsentStatus($lead);

            activity()->causedBy(auth()->user())->performedOn($record)->log('Consent record added');

            flash()->success('Consent record saved.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function update(Request $request, Lead $lead, LeadConsentRecord $consent)
    {
        // Only allow withdrawal updates
        try {
            $consent->status       = 'Withdrawn';
            $consent->withdrawn_at = now();
            $consent->save();

            // Roll up consent_status on the Lead
            $this->leadService->rollUpConsentStatus($lead);

            activity()->causedBy(auth()->user())->performedOn($consent)->log('Consent withdrawn');

            flash()->success('Consent withdrawn.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }
}
