<?php

namespace App\Admin\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Admin\Models\Lead;
use App\Admin\Models\LeadConsentRecord;
use App\Admin\Services\LeadService;
use App\Admin\Authorization\CrmRecordAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsentController extends Controller
{
    public function __construct(protected LeadService $leadService, protected CrmRecordAccess $recordAccess) {}

    public function store(Request $request, Lead $lead)
    {
        $this->recordAccess->assertLead($request->user(), $lead);
        $validated = $request->validate([
            'channel' => ['required', Rule::in(config('crm.consent_channels'))],
            'status'  => ['required', Rule::in(['Unknown', 'Granted', 'Withdrawn'])],
            'source'  => ['nullable', 'string', 'max:200'],
        ]);

        try {
            [$record, $previousChannelStatus, $previousAggregateStatus, $newAggregateStatus] = DB::transaction(function () use ($lead, $validated): array {
                $lockedLead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
                $previousChannelStatus = $lockedLead->consentRecords()->where('channel', $validated['channel'])->orderByDesc('id')->value('status') ?? 'Unknown';
                $previousAggregateStatus = $this->leadService->currentConsentStatus($lockedLead);
                $validated['lead_id'] = $lockedLead->id;
                $validated['created_by'] = auth()->id();
                $validated['consented_at'] = $validated['status'] === 'Granted' ? now() : null;
                $validated['withdrawn_at'] = $validated['status'] === 'Withdrawn' ? now() : null;
                $record = LeadConsentRecord::create($validated);
                $this->leadService->rollUpConsentStatus($lockedLead);

                return [$record, $previousChannelStatus, $previousAggregateStatus, $lockedLead->consent_status];
            });

            activity()->causedBy(auth()->user())->performedOn($record)
                ->withProperties([
                    'channel' => $record->channel,
                    'previous_channel_status' => $previousChannelStatus,
                    'new_channel_status' => $record->status,
                    'previous_aggregate_status' => $previousAggregateStatus,
                    'new_aggregate_status' => $newAggregateStatus,
                ])
                ->log('Consent record added');

            flash()->success('Consent record saved.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function update(Request $request, Lead $lead, LeadConsentRecord $consent)
    {
        $this->recordAccess->assertLead($request->user(), $lead);
        abort_unless((int) $consent->lead_id === (int) $lead->id, 404);
        $latestId = $lead->consentRecords()->where('channel', $consent->channel)->orderByDesc('id')->value('id');
        abort_unless((int) $latestId === (int) $consent->id && $consent->status === 'Granted', 409, 'Only the current granted consent can be withdrawn.');

        try {
            $change = DB::transaction(function () use ($lead, $consent): ?array {
                $lockedLead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
                $latestId = $lockedLead->consentRecords()->where('channel', $consent->channel)->orderByDesc('id')->value('id');
                if ((int) $latestId !== (int) $consent->id || $consent->status !== 'Granted') {
                    return null;
                }
                $previousAggregateStatus = $this->leadService->currentConsentStatus($lockedLead);
                $withdrawal = LeadConsentRecord::query()->create([
                    'lead_id' => $lockedLead->id,
                    'channel' => $consent->channel,
                    'status' => 'Withdrawn',
                    'source' => 'Staff UI withdrawal action',
                    'withdrawn_at' => now(),
                    'created_by' => auth()->id(),
                ]);
                $this->leadService->rollUpConsentStatus($lockedLead);

                return [$withdrawal, $previousAggregateStatus, $lockedLead->consent_status];
            });
            abort_unless($change, 409, 'Consent changed before the withdrawal was recorded.');
            [$withdrawal, $previousAggregateStatus, $newAggregateStatus] = $change;

            activity()->causedBy(auth()->user())->performedOn($withdrawal)
                ->withProperties([
                    'channel' => $withdrawal->channel,
                    'previous_channel_status' => 'Granted',
                    'new_channel_status' => 'Withdrawn',
                    'previous_aggregate_status' => $previousAggregateStatus,
                    'new_aggregate_status' => $newAggregateStatus,
                ])
                ->log('Consent withdrawn');

            flash()->success('Consent withdrawn.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }
}
