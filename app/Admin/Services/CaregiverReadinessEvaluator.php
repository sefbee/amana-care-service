<?php

namespace App\Admin\Services;

use App\Admin\Models\Caregiver;
use Illuminate\Support\Collection;

class CaregiverReadinessEvaluator
{
    /** @return array{ready: bool, reasons: array<int, string>} */
    public function evaluate(Caregiver $caregiver, ?Collection $mandatoryTrainingModuleIds = null): array
    {
        $reasons = [];
        $today = now()->toDateString();

        foreach (['Identity', 'Police', 'Psych'] as $type) {
            $checks = $caregiver->relationLoaded('screeningChecks')
                ? $caregiver->screeningChecks
                : $caregiver->screeningChecks()->get();
            $check = $checks->where('type', $type)->sortByDesc('checked_at')->first();
            if (! $check || $check->outcome !== 'Pass') {
                $reasons[] = $type.' check has not passed';
                continue;
            }
            if ($check->verification_status !== 'Verified') {
                $reasons[] = $type.' historical check needs verification';
                continue;
            }
            if (! $check->approved_at || ! $check->approved_by) {
                $reasons[] = $type.' check has not been approved by an authorized screening approver';
            }
            if ($type === 'Police' && (! $check->valid_until || $check->valid_until->lt($today))) {
                $reasons[] = 'Police check is expired or has no expiry date';
            }
        }

        $mandatoryIds = $mandatoryTrainingModuleIds ?? \App\Admin\Models\TrainingModule::query()
            ->where('is_active', true)->where('is_mandatory', true)->pluck('id');
        if ($mandatoryIds->isEmpty()) {
            $reasons[] = 'Mandatory training requirements are not configured';
        }
        $trainingRecords = $caregiver->relationLoaded('trainingRecords')
            ? $caregiver->trainingRecords
            : $caregiver->trainingRecords()->get();
        $passedIds = $trainingRecords->where('outcome', 'Pass')->pluck('training_module_id')->intersect($mandatoryIds);
        if ($mandatoryIds->diff($passedIds)->isNotEmpty()) {
            $reasons[] = 'Mandatory training is incomplete';
        }

        if ($caregiver->safeguarding_hold_at) {
            $reasons[] = 'Active safeguarding hold';
        }

        $approval = $caregiver->relationLoaded('readinessApprovals')
            ? $caregiver->readinessApprovals->sortByDesc('approved_at')->first()
            : $caregiver->readinessApprovals()->first();
        if (! $approval?->approved) {
            $reasons[] = 'Operations Manager approval is missing';
        }

        return ['ready' => $reasons === [], 'reasons' => $reasons];
    }

    public function refresh(Caregiver $caregiver, ?Collection $mandatoryTrainingModuleIds = null, ?int $actorId = null): bool
    {
        $result = $this->evaluate($caregiver, $mandatoryTrainingModuleIds);
        $newStatus = $result['ready'] ? 'Ready' : 'Not Ready';
        if ($caregiver->status !== $newStatus) {
            $previousStatus = $caregiver->status;
            $caregiver->forceFill(['status' => $newStatus])->save();
            $activity = activity('caregiver_readiness')->performedOn($caregiver);
            if ($actorId) $activity->causedBy($actorId);
            $activity->withProperties(['from' => $previousStatus, 'to' => $newStatus, 'reasons' => $result['reasons']])
                ->log('Caregiver readiness status changed');
        }

        if (! $result['ready']) {
            app(ProposalReadinessWithdrawal::class)->withdrawDrafts($caregiver, $actorId);
        }

        return $result['ready'];
    }
}
