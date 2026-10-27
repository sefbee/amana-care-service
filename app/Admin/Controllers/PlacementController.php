<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\Caregiver;
use App\Admin\Models\MatchProposalCandidate;
use App\Admin\Models\MatchProposalEvent;
use App\Admin\Models\Placement;
use App\Admin\Models\PlacementEvent;
use App\Admin\Models\PlacementScheduleSlot;
use App\Admin\Models\Quote;
use App\Admin\Models\TrainingModule;
use App\Admin\Services\CaregiverReadinessEvaluator;
use App\Admin\Services\PlacementAgreementVault;
use App\Admin\Services\PlacementFollowUpScheduler;
use App\Admin\Services\WeeklySchedule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;

class PlacementController extends Controller
{
    public function familySummary(Request $request, \App\Admin\Models\MatchProposal $proposal, CrmRecordAccess $access)
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['co', 'om', 'platform-superuser'], true), 403);
        $proposal->load(['serviceRequest.household', 'serviceRequest.service', 'candidates.caregiver.skills']);
        $access->assertHousehold($request->user(), $proposal->serviceRequest->household);
        abort_unless($proposal->status === 'Draft', 422, 'Only an active draft proposal can be exported for family review.');
        $visibleCaregiverIds = $access->caregivers(Caregiver::query(), $request->user())->pluck('id')->all();
        $visibleCandidates = $proposal->candidates->filter(fn (MatchProposalCandidate $candidate): bool => in_array((int) $candidate->caregiver_id, array_map('intval', $visibleCaregiverIds), true))->values();
        $candidates = $visibleCandidates->map(function (MatchProposalCandidate $candidate, int $index) use ($proposal): array {
            $caregiver = $candidate->caregiver;
            $proficient = $caregiver->skills->where('is_proficient', true)->pluck('skill_key')->all();
            $required = $proposal->serviceRequest->service->required_skill_keys ?? [];
            return [
                'label' => 'Caregiver '.chr(65 + $index),
                'first_name' => collect(preg_split('/\s+/u', trim((string) $caregiver->name)))->first() ?: 'Caregiver',
                'skills' => collect(array_intersect($required, $proficient))
                    ->map(fn (string $key) => config('caregivers.skills.'.$key, $key))->values()->all(),
                'available_from' => $caregiver->available_from?->format('d M Y'),
                'zone_match' => in_array($proposal->serviceRequest->household->area, $caregiver->zone_preferences ?? [], true),
            ];
        });
        $settings = \App\FrontEnd\Cms\Models\CmsSetting::query()->whereNotNull('published_value')->get()->mapWithKeys(
            fn ($setting) => [$setting->key => data_get($setting->published_value, 'value')]
        );
        $brand = [
            'name' => $settings->get('site_name') ?: 'Amanah Care Service',
            'tagline' => $settings->get('tagline') ?: 'Trusted care for every family',
            'primary_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $settings->get('primary_color')) ? $settings->get('primary_color') : '#14532d',
        ];

        activity('matching_family_summary')->causedBy($request->user())->performedOn($proposal)
            ->withProperties(['candidate_ids' => $visibleCandidates->pluck('caregiver_id')->all(), 'candidate_count' => $candidates->count()])
            ->log('Redacted family proposal PDF generated');

        return Pdf::loadView('admin.matching.family-summary', [
            'proposal' => $proposal,
            'serviceRequest' => $proposal->serviceRequest,
            'candidates' => $candidates,
            'brand' => $brand,
        ])->setPaper('a4')->download('amanah-caregiver-proposal-'.$proposal->id.'.pdf');
    }

    public function activate(Request $request, MatchProposalCandidate $candidate, CrmRecordAccess $access, CaregiverReadinessEvaluator $readiness, PlacementAgreementVault $vault, WeeklySchedule $weeklySchedule, PlacementFollowUpScheduler $followUps)
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['co', 'platform-superuser'], true), 403);
        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'agreement' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $candidate->load(['proposal.serviceRequest.household']);
        $access->assertHousehold($request->user(), $candidate->proposal->serviceRequest->household);
        $candidate->loadMissing('caregiver');
        $access->assertCaregiver($request->user(), $candidate->caregiver);

        $storedAgreement = $vault->store($data['agreement']);
        try {
            $placement = DB::transaction(function () use ($request, $candidate, $access, $readiness, $data, $storedAgreement, $weeklySchedule, $followUps): ?Placement {
                $lockedCandidate = MatchProposalCandidate::query()->lockForUpdate()->findOrFail($candidate->id);
                $lockedCandidate->load(['proposal.serviceRequest.household', 'proposal.serviceRequest.service', 'caregiver']);
                $access->assertCaregiver($request->user(), $lockedCandidate->caregiver);
                $proposal = $lockedCandidate->proposal;
                $serviceRequest = $proposal->serviceRequest;
                $access->assertHousehold($request->user(), $serviceRequest->household);
                abort_unless($proposal->status === 'Draft', 422, 'This proposal is no longer active.');
                abort_if(Placement::query()->where('match_proposal_candidate_id', $lockedCandidate->id)->exists(), 422, 'This proposed candidate already has a placement.');

                $acceptedEvent = MatchProposalEvent::query()
                    ->where('match_proposal_id', $proposal->id)
                    ->where('match_proposal_candidate_id', $lockedCandidate->id)
                    ->where('event_type', 'FamilyDecisionRecorded')
                    ->where('outcome', 'Accepted')
                    ->lockForUpdate()
                    ->first();
                abort_unless($acceptedEvent, 422, 'A recorded family acceptance for this caregiver is required before placement.');

                // Serialize every activation for this caregiver before checking schedule rows.
                $caregiver = Caregiver::query()->lockForUpdate()->findOrFail($lockedCandidate->caregiver_id);
                $mandatoryTrainingIds = TrainingModule::query()->where('is_active', true)->where('is_mandatory', true)->pluck('id');
                if (! $readiness->refresh($caregiver, $mandatoryTrainingIds, (int) $request->user()->id)) {
                    return null;
                }

                $schedule = $serviceRequest->schedule_json;
                $requiredSkills = $serviceRequest->service->required_skill_keys ?? [];
                $caregiver->load('skills');
                abort_if($requiredSkills === [], 422, 'Service skill requirements are not configured; placement activation is blocked.');
                abort_if(array_diff($requiredSkills, $caregiver->skills->where('is_proficient', true)->pluck('skill_key')->all()) !== [], 422, 'Caregiver no longer meets the service skill requirements.');
                if ($serviceRequest->live_in_out === 'live-in') {
                    abort_unless($caregiver->live_in_preferred, 422, 'Caregiver live-in preference conflicts with this request.');
                } elseif ($serviceRequest->live_in_out === 'live-out') {
                    abort_if($caregiver->live_in_preferred, 422, 'Caregiver live-out preference conflicts with this request.');
                }
                abort_unless($caregiver->available_from && $caregiver->available_from->toDateString() <= $data['start_date'], 422, 'Caregiver availability does not cover the placement start date.');

                $slots = $weeklySchedule->slots($schedule);
                $this->assertNoOverlap($caregiver->id, $data['start_date'], $data['end_date'] ?? null, $slots, $weeklySchedule);

                $quote = Quote::query()->where('service_request_id', $serviceRequest->id)->where('status', 'Approved')->latest('version')->with('lines.priceVersion')->first();
                abort_unless($quote && $quote->lines->count() === 1 && (int) $quote->lines->first()->service_id === (int) $serviceRequest->service_id && $quote->final_total !== null, 422, 'An approved quote for this service request is required.');
                $line = $quote->lines->first();
                $activePrice = $serviceRequest->service->currentPrice();
                abort_unless($activePrice && $activePrice->id === $line->price_version_id, 422, 'The approved quote price is no longer effective. Reissue and approve a current quote before activating.');

                $status = now()->toDateString() < $data['start_date'] ? 'Scheduled' : 'Active';
                $placement = Placement::query()->create([
                    'service_request_id' => $serviceRequest->id,
                    'match_proposal_candidate_id' => $lockedCandidate->id,
                    'household_id' => $serviceRequest->household_id,
                    'caregiver_id' => $caregiver->id,
                    'quote_id' => $quote->id,
                    'price_version_id' => $line->price_version_id,
                    'price_amount_snapshot' => $line->unit_amount,
                    'quote_total_snapshot' => $quote->final_total,
                    'currency' => $quote->currency,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'] ?? null,
                    'schedule_snapshot' => $schedule,
                    'status' => $status,
                    'agreement_disk' => $storedAgreement['disk'],
                    'agreement_key' => $storedAgreement['key'],
                    'agreement_mime' => $storedAgreement['mime'],
                    'agreement_size_bytes' => $storedAgreement['size'],
                    'agreement_sha256' => $storedAgreement['sha256'],
                    'activated_by' => $request->user()->id,
                    'activated_at' => $status === 'Active' ? now() : null,
                ]);
                foreach ($slots as $slot) {
                    PlacementScheduleSlot::query()->create(['placement_id' => $placement->id] + $slot);
                }
                PlacementEvent::query()->create(['placement_id' => $placement->id, 'event_type' => $status, 'actor_id' => $request->user()->id, 'occurred_at' => now()]);
                MatchProposalEvent::query()->create([
                    'match_proposal_id' => $proposal->id,
                    'match_proposal_candidate_id' => $lockedCandidate->id,
                    'event_type' => $status === 'Active' ? 'PlacementActivated' : 'PlacementScheduled',
                    'outcome' => $status,
                    'actor_id' => $request->user()->id,
                    'occurred_at' => now(),
                ]);
                $proposal->forceFill(['status' => 'Placed'])->save();
                if ($status === 'Active') {
                    $followUps->schedule($placement);
                }
                activity('placement')->causedBy($request->user())->performedOn($placement)
                    ->withProperties([
                        'service_request_id' => $serviceRequest->id,
                        'caregiver_id' => $caregiver->id,
                        'quote_id' => $quote->id,
                        'price_version_id' => $line->price_version_id,
                        'agreement_sha256' => $storedAgreement['sha256'],
                        'schedule_weekdays' => array_column($slots, 'weekday'),
                ])->log($status === 'Active' ? 'Caregiver placement activated' : 'Caregiver placement scheduled');

                return $placement;
            });
        } catch (Throwable $exception) {
            $vault->delete($storedAgreement['disk'], $storedAgreement['key']);
            throw $exception;
        }
        if (! $placement) {
            $vault->delete($storedAgreement['disk'], $storedAgreement['key']);
            throw ValidationException::withMessages(['caregiver' => 'Caregiver is no longer Ready. The proposal was withdrawn and placement activation is blocked.']);
        }

        return redirect()->route('households.show', $placement->household_id)->with('success', 'Placement activated with a verified agreement and approved price snapshot.');
    }

    public function transition(Request $request, Placement $placement, CrmRecordAccess $access)
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['co', 'om', 'platform-superuser'], true), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['Paused', 'Ended', 'Cancelled'])],
            'rationale' => ['required', 'string', 'min:20', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $placement, $access, $data): void {
            $caregiver = Caregiver::withTrashed()->lockForUpdate()->findOrFail($placement->caregiver_id);
            $access->assertCaregiver($request->user(), $caregiver);
            $locked = Placement::query()->lockForUpdate()->findOrFail($placement->id);
            $access->assertHousehold($request->user(), $locked->household);
            $allowed = [
                'Active' => ['Paused', 'Ended', 'Cancelled'],
                'Scheduled' => ['Active', 'Paused', 'Cancelled'],
                'Paused' => ['Ended', 'Cancelled'],
            ];
            abort_unless(in_array($data['status'], $allowed[$locked->status] ?? [], true), 422, 'This placement lifecycle transition is not allowed.');

            $from = $locked->status;
            $timestamp = now();
            $locked->forceFill([
                'status' => $data['status'],
                'paused_at' => $data['status'] === 'Paused' ? $timestamp : $locked->paused_at,
                'ended_at' => in_array($data['status'], ['Ended', 'Cancelled'], true) ? $timestamp : $locked->ended_at,
            ])->save();
            PlacementEvent::query()->create([
                'placement_id' => $locked->id,
                'event_type' => $data['status'],
                'rationale' => trim($data['rationale']),
                'actor_id' => $request->user()->id,
                'occurred_at' => $timestamp,
            ]);
            activity('placement')->causedBy($request->user())->performedOn($locked)
                ->withProperties(['from' => $from, 'to' => $data['status'], 'rationale_length' => mb_strlen(trim($data['rationale']))])
                ->log('Placement lifecycle status changed');
        });

        return redirect()->route('households.show', $placement->household_id)->with('success', 'Placement status updated and audited.');
    }

    public function revealAgreement(Request $request, Placement $placement, CrmRecordAccess $access, PlacementAgreementVault $vault)
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['co', 'om', 'platform-superuser'], true), 403);
        $access->assertHousehold($request->user(), $placement->household);
        $access->assertCaregiver($request->user(), $placement->caregiver);
        $data = $request->validate(['reason' => ['required', 'string', 'min:20', 'max:500']]);
        $url = $vault->revealUrl($placement, (int) $request->user()->id, trim($data['reason']));
        abort_if($url === '', 403, 'Agreement access is not available for this placement.');

        return redirect()->away($url);
    }

    private function assertNoOverlap(int $caregiverId, string $startDate, ?string $endDate, array $requestedSlots, WeeklySchedule $weeklySchedule): void
    {
        $existing = Placement::query()->where('caregiver_id', $caregiverId)->whereIn('status', ['Active', 'Scheduled'])
            ->whereDate('start_date', '<=', $endDate ?? '9999-12-31')
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $startDate))
            ->with('slots')->lockForUpdate()->get();
        foreach ($existing as $placement) {
            foreach ($placement->slots as $existingSlot) {
                foreach ($requestedSlots as $requestedSlot) {
                    if ($weeklySchedule->overlaps($requestedSlot, $existingSlot->toArray())) {
                        throw ValidationException::withMessages(['schedule' => 'This caregiver already has an active placement on an overlapping weekday and time. Choose another caregiver or revise the schedule.']);
                    }
                }
            }
        }
    }

}
