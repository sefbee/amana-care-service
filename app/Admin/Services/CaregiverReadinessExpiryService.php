<?php

namespace App\Admin\Services;

use App\Admin\Models\Caregiver;
use App\Admin\Models\CaregiverReadinessAlert;
use App\Admin\Models\CaregiverScreeningCheck;
use App\Admin\Models\MatchProposal;
use Illuminate\Support\Facades\DB;

class CaregiverReadinessExpiryService
{
    public function run(): array
    {
        $today = today()->toDateString();
        $warningEnd = today()->addDays(30)->toDateString();
        $currentPoliceChecks = CaregiverScreeningCheck::query()
            ->whereHas('caregiver')
            ->where('type', 'Police')
            ->where('outcome', 'Pass')
            ->whereNotNull('valid_until')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('caregiver_screening_checks as newer')
                    ->whereColumn('newer.caregiver_id', 'caregiver_screening_checks.caregiver_id')
                    ->where('newer.type', 'Police')
                    ->where(function ($sub): void {
                        $sub->whereColumn('newer.checked_at', '>', 'caregiver_screening_checks.checked_at')
                            ->orWhere(function ($tie): void {
                                $tie->whereColumn('newer.checked_at', 'caregiver_screening_checks.checked_at')
                                    ->whereColumn('newer.id', '>', 'caregiver_screening_checks.id');
                            });
                    });
            });

        $checks = $currentPoliceChecks->whereDate('valid_until', '<=', $warningEnd)->get();
        $created = 0;
        $withdrawn = 0;
        $expiredCaregivers = 0;
        foreach ($checks as $observedCheck) {
            DB::transaction(function () use ($observedCheck, $today, $warningEnd, &$created, &$withdrawn, &$expiredCaregivers): void {
                $caregiverId = $observedCheck->caregiver_id;
                $caregiver = Caregiver::query()->lockForUpdate()->find($caregiverId);
                if (! $caregiver) return;
                $currentCheck = CaregiverScreeningCheck::query()
                    ->where('caregiver_id', $caregiver->id)->where('type', 'Police')
                    ->orderByDesc('checked_at')->orderByDesc('id')->lockForUpdate()->first();
                if (! $currentCheck || (int) $currentCheck->id !== (int) $observedCheck->id || $currentCheck->outcome !== 'Pass' || ! $currentCheck->valid_until || $currentCheck->valid_until->gt($warningEnd)) return;

                $alertType = $currentCheck->valid_until->lt($today) ? 'Expired' : 'ExpiringSoon';
                $alert = CaregiverReadinessAlert::query()->firstOrCreate(
                    ['screening_check_id' => $currentCheck->id, 'alert_type' => $alertType],
                    ['caregiver_id' => $caregiver->id, 'valid_until' => $currentCheck->valid_until->toDateString()]
                );
                $created += (int) $alert->wasRecentlyCreated;
                if ($alert->wasRecentlyCreated) {
                    activity('caregiver_readiness_alert')->performedOn($alert)
                        ->withProperties([
                            'caregiver_id' => $caregiver->id,
                            'screening_check_id' => $currentCheck->id,
                            'alert_type' => $alertType,
                            'valid_until' => $currentCheck->valid_until->toDateString(),
                        ])->log('Caregiver police-check expiry alert created for RS queue');
                }

                if ($alertType !== 'Expired') return;

                $draftProposalCount = MatchProposal::query()->where('status', 'Draft')
                    ->whereHas('candidates', fn ($query) => $query->where('caregiver_id', $caregiver->id))->count();
                $ready = app(CaregiverReadinessEvaluator::class)->refresh($caregiver);
                $expiredCaregivers++;
                if (! $ready) $withdrawn += $draftProposalCount;
            });
        }

        return ['alerts_created' => $created, 'proposals_withdrawn' => $withdrawn, 'expired_caregivers' => $expiredCaregivers];
    }
}
