<?php

namespace App\Admin\Services;

use App\Admin\Models\Caregiver;
use App\Admin\Models\CaregiverSkill;
use App\Admin\Models\CaregiverStageHistory;
use App\Admin\Services\LeadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaregiverService
{
    public function __construct(protected LeadService $leadService) {}

    public function create(array $data, array $skills, int $actorId, string $recordSource = 'Operational'): Caregiver
    {
        if (! in_array($recordSource, ['Operational', 'Historical Import'], true)) {
            throw new \InvalidArgumentException('Unsupported caregiver record source.');
        }

        $nid = strtoupper(preg_replace('/[\s-]+/', '', (string) $data['national_id']));
        if ($nid === '' || strlen($nid) > 64) {
            throw ValidationException::withMessages(['national_id' => 'Enter a valid National ID value.']);
        }

        $key = config('caregivers.nid_hash_key') ?: config('app.key');
        $fingerprint = hash_hmac('sha256', $nid, (string) $key);
        if (Caregiver::withTrashed()->where('national_id_fingerprint', $fingerprint)->exists()) {
            throw ValidationException::withMessages(['national_id' => 'A candidate with this National ID already exists. Review the existing candidate instead of creating a duplicate.']);
        }
        $allowedSkills = array_keys(config('caregivers.skills', []));
        $unknownSkills = array_diff(array_keys(array_filter($skills)), $allowedSkills);
        if ($unknownSkills !== []) {
            throw ValidationException::withMessages(['skills' => 'One or more selected skills are not configured.']);
        }

        return DB::transaction(function () use ($data, $skills, $actorId, $fingerprint): Caregiver {
            $caregiver = Caregiver::query()->create([
                'name' => trim($data['name']),
                'dob' => $data['dob'],
                'gender' => $data['gender'] ?? null,
                'phone' => trim($data['phone']),
                'normalized_phone' => $this->leadService->normalizePhone($data['phone']),
                'national_id_hash' => $fingerprint,
                'national_id_fingerprint' => $fingerprint,
                'live_in_preferred' => (bool) ($data['live_in_preferred'] ?? false),
                'available_from' => $data['available_from'] ?? null,
                'zone_preferences' => $data['zone_preferences'] ?? [],
                'recruitment_stage' => 'New',
                'record_source' => $recordSource,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            foreach (config('caregivers.skills', []) as $skillKey => $label) {
                CaregiverSkill::query()->create([
                    'caregiver_id' => $caregiver->id,
                    'skill_key' => $skillKey,
                    'is_proficient' => (bool) ($skills[$skillKey] ?? false),
                    'recorded_by' => $actorId,
                ]);
            }

            CaregiverStageHistory::query()->create([
                'caregiver_id' => $caregiver->id,
                'from_stage' => null,
                'to_stage' => 'New',
                'changed_by' => $actorId,
            ]);

            activity('caregiver_recruitment')
                ->causedBy($actorId)
                ->performedOn($caregiver)
                ->withProperties(['skill_keys' => array_keys(array_filter($skills))])
                ->log('Caregiver candidate created');

            return $caregiver;
        });
    }

    public function update(Caregiver $caregiver, array $data, array $skills, int $actorId): Caregiver
    {
        $allowedSkills = array_keys(config('caregivers.skills', []));
        if (array_diff(array_keys(array_filter($skills)), $allowedSkills) !== []) {
            throw ValidationException::withMessages(['skills' => 'One or more selected skills are not configured.']);
        }

        return DB::transaction(function () use ($caregiver, $data, $skills, $actorId): Caregiver {
            $caregiver = Caregiver::query()->lockForUpdate()->findOrFail($caregiver->id);
            $caregiver->fill([
                'name' => trim($data['name']),
                'dob' => $data['dob'],
                'gender' => $data['gender'] ?? null,
                'phone' => trim($data['phone']),
                'normalized_phone' => $this->leadService->normalizePhone($data['phone']),
                'live_in_preferred' => (bool) ($data['live_in_preferred'] ?? false),
                'available_from' => $data['available_from'] ?? null,
                'zone_preferences' => $data['zone_preferences'] ?? [],
                'updated_by' => $actorId,
            ])->save();

            foreach (config('caregivers.skills', []) as $skillKey => $label) {
                CaregiverSkill::query()->updateOrCreate(
                    ['caregiver_id' => $caregiver->id, 'skill_key' => $skillKey],
                    ['is_proficient' => (bool) ($skills[$skillKey] ?? false), 'recorded_by' => $actorId]
                );
            }

            app(CaregiverReadinessEvaluator::class)->refresh($caregiver, actorId: $actorId);

            activity('caregiver_recruitment')->causedBy($actorId)->performedOn($caregiver)
                ->withProperties(['updated_fields' => ['profile', 'availability', 'skills']])
                ->log('Caregiver candidate profile updated');

            return $caregiver->refresh();
        });
    }
}
