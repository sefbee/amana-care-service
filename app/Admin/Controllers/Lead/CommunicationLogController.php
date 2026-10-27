<?php

namespace App\Admin\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Admin\Models\Lead;
use App\Admin\Models\LeadCommunicationLog;
use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommunicationLogController extends Controller
{
    public function __construct(protected CrmRecordAccess $recordAccess, protected LeadService $leadService) {}

    public function store(Request $request, Lead $lead)
    {
        $this->recordAccess->assertLead($request->user(), $lead);
        $validated = $request->validate([
            'contact_date' => ['required', 'date', 'before_or_equal:now'],
            'direction'    => ['required', Rule::in(['Inbound', 'Outbound'])],
            'channel'      => ['required', Rule::in(config('crm.log_channels'))],
            'summary'      => ['required', 'string', 'max:1000'],
            'next_step'    => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $log = DB::transaction(function () use ($lead, $validated): ?LeadCommunicationLog {
                $lockedLead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
                if ($validated['direction'] === 'Outbound') {
                    $aggregateGranted = $this->leadService->currentConsentStatus($lockedLead) === 'Granted';
                    $channelConsentGranted = ! in_array($validated['channel'], ['Email', 'SMS'], true)
                        || $this->leadService->hasCurrentConsentForChannel($lockedLead, $validated['channel']);
                    if (! $aggregateGranted || ! $channelConsentGranted) {
                        return null;
                    }
                }

                $validated['lead_id'] = $lockedLead->id;
                $validated['created_by'] = auth()->id();

                return LeadCommunicationLog::create($validated);
            });

            if (! $log) {
                return back()->withErrors([
                    'direction' => 'Outbound communication requires current Granted consent for the selected channel (BR-020). Please update consent first.',
                ])->withInput();
            }

            activity()->causedBy(auth()->user())->performedOn($log)->log('Communication log entry added');

            flash()->success('Communication log entry added.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function destroy(Lead $lead, LeadCommunicationLog $log)
    {
        $this->recordAccess->assertLead(request()->user(), $lead);
        abort_unless((int) $log->lead_id === (int) $lead->id, 404);

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
