<?php

namespace App\Admin\Services;

use App\Admin\Models\Caregiver;
use App\Admin\Models\ServiceRequest;
use App\Admin\Models\User;
use App\Admin\Authorization\CrmRecordAccess;
use Illuminate\Support\Collection;

class CandidateMatcher
{
    /** @return array{required_skills: array, included: Collection, excluded: Collection} */
    public function filter(ServiceRequest $serviceRequest, Collection $mandatoryTrainingIds, bool $lockCandidates = false, ?int $actorId = null, ?User $actor = null): array
    {
        $requiredSkills = $serviceRequest->service->required_skill_keys ?? [];
        $query = Caregiver::query()
            ->select(['id', 'name', 'live_in_preferred', 'available_from', 'zone_preferences', 'status', 'safeguarding_hold_at'])
            ->with(['skills', 'screeningChecks', 'trainingRecords', 'readinessApprovals', 'placements.slots'])
            ->orderBy('name');
        if ($actor) {
            $query = app(CrmRecordAccess::class)->caregivers($query, $actor);
        }
        if ($lockCandidates) {
            $query->lockForUpdate();
        }
        $caregivers = $query->get();
        $included = collect();
        $excluded = collect();
        $readiness = app(CaregiverReadinessEvaluator::class);
        $weeklySchedule = app(WeeklySchedule::class);
        $requestSlots = $weeklySchedule->slots($serviceRequest->schedule_json ?? []);

        foreach ($caregivers as $caregiver) {
            $reasons = [];
            $readinessResult = $readiness->evaluate($caregiver, $mandatoryTrainingIds);
            if (! $readinessResult['ready']) {
                $reasons = array_merge($reasons, $readinessResult['reasons']);
            } elseif ($lockCandidates) {
                $readiness->refresh($caregiver, $mandatoryTrainingIds, $actorId);
            }
            if ($requiredSkills === []) {
                $reasons[] = 'Service has no configured caregiver skill requirements';
            } else {
                $proficient = $caregiver->skills->where('is_proficient', true)->pluck('skill_key');
                foreach (array_diff($requiredSkills, $proficient->all()) as $missingSkill) {
                    $reasons[] = 'Missing required skill: '.config('caregivers.skills.'.$missingSkill, $missingSkill);
                }
            }
            if ($serviceRequest->live_in_out === 'live-in' && ! $caregiver->live_in_preferred) {
                $reasons[] = 'Live-in preference conflicts with the request';
            } elseif ($serviceRequest->live_in_out === 'live-out' && $caregiver->live_in_preferred) {
                $reasons[] = 'Live-out preference conflicts with the request';
            }
            foreach ($caregiver->placements->whereIn('status', ['Active', 'Scheduled']) as $placement) {
                if ($placement->end_date && $placement->end_date->lt(today())) {
                    continue;
                }
                foreach ($placement->slots as $slot) {
                    if (collect($requestSlots)->contains(fn (array $requestSlot) => $weeklySchedule->overlaps($requestSlot, $slot->toArray()))) {
                        $reasons[] = 'Overlaps an existing active or scheduled placement';
                        break 2;
                    }
                }
            }

            $candidate = [
                'id' => $caregiver->id,
                'name' => $caregiver->name,
                'status' => $readinessResult['ready'] ? 'Ready' : 'Not Ready',
                'available_from' => $caregiver->available_from,
                'zone_match' => in_array($serviceRequest->household->area, $caregiver->zone_preferences ?? [], true),
                'skills' => $caregiver->skills->where('is_proficient', true)->pluck('skill_key')
                    ->map(fn (string $key) => config('caregivers.skills.'.$key, $key))->values(),
                'reasons' => array_values(array_unique($reasons)),
            ];
            ($reasons === [] ? $included : $excluded)->push($candidate);
        }

        return ['required_skills' => $requiredSkills, 'included' => $included, 'excluded' => $excluded];
    }

}
