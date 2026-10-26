<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadCommunicationLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunicationLogController extends Controller
{
    public function store(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'contact_date' => ['required', 'date', 'before_or_equal:now'],
            'direction'    => ['required', Rule::in(['Inbound', 'Outbound'])],
            'channel'      => ['required', Rule::in(config('crm.log_channels'))],
            'summary'      => ['required', 'string', 'max:1000'],
            'next_step'    => ['nullable', 'string', 'max:500'],
        ]);

        // BR-020: Block outbound if consent not granted
        if ($validated['direction'] === 'Outbound' && $lead->consent_status !== 'Granted') {
            return back()->withErrors([
                'direction' => 'Outbound messages require Granted consent (BR-020). Please update consent first.',
            ])->withInput();
        }

        try {
            $validated['lead_id']    = $lead->id;
            $validated['created_by'] = auth()->id();

            $log = LeadCommunicationLog::create($validated);

            activity()->causedBy(auth()->user())->performedOn($log)->log('Communication log entry added');

            flash()->success('Communication log entry added.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function destroy(Lead $lead, LeadCommunicationLog $log)
    {
        try {
            activity()->causedBy(auth()->user())->performedOn($log)->log('Communication log entry deleted');
            $log->delete();
            flash()->success('Log entry deleted.');
        } catch (\Exception $e) {
            flash()->error('Failed to delete log entry.');
        }

        return back();
    }
}
