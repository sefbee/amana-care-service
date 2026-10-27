<?php

namespace App\Admin\Authorization;

use App\Admin\Models\Household;
use App\Admin\Models\Lead;
use App\Admin\Models\Role;
use App\Admin\Models\Caregiver;
use App\Admin\Models\RecordConflictBlock;
use App\Admin\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CrmRecordAccess
{
    public function roleSlug(User $user): ?string
    {
        if ($user->isPlatformSuperuser()) {
            return 'platform-superuser';
        }

        $roleId = session('role_id', $user->primary_role_id);

        return Role::query()
            ->whereKey($roleId)
            ->where('roles.is_active', true)
            ->whereHas('users', fn ($query) => $query->where('users.id', $user->id)->where('role_user.is_active', true))
            ->value('slug');
    }

    public function leads(Builder $query, User $user): Builder
    {
        $query = match ($this->roleSlug($user)) {
            'platform-superuser', 'om' => $query,
            'co' => $query->where('owner_id', $user->id),
            default => $query->whereRaw('1 = 0'),
        };

        if (! $user->isPlatformSuperuser()) {
            $query->whereNotExists(fn ($blocked) => $blocked->selectRaw('1')->from('record_conflict_blocks')
                ->whereColumn('record_conflict_blocks.household_id', 'leads.household_id')
                ->where('record_conflict_blocks.staff_user_id', $user->id)
                ->whereNull('record_conflict_blocks.revoked_at'));
        }

        return $query;
    }

    public function households(Builder $query, User $user): Builder
    {
        $query = match ($this->roleSlug($user)) {
            'platform-superuser', 'om', 'fn' => $query,
            'co' => $query->where('assigned_coordinator_id', $user->id),
            default => $query->whereRaw('1 = 0'),
        };

        if (! $user->isPlatformSuperuser()) {
            $query->whereNotExists(fn ($blocked) => $blocked->selectRaw('1')->from('record_conflict_blocks')
                ->whereColumn('record_conflict_blocks.household_id', 'households.id')
                ->where('record_conflict_blocks.staff_user_id', $user->id)
                ->whereNull('record_conflict_blocks.revoked_at'));
        }

        return $query;
    }

    public function caregivers(Builder $query, User $user): Builder
    {
        if ($user->isPlatformSuperuser()) {
            return $query;
        }

        return $query->whereNotExists(fn ($blocked) => $blocked->selectRaw('1')->from('record_conflict_blocks')
            ->whereColumn('record_conflict_blocks.caregiver_id', 'caregivers.id')
            ->where('record_conflict_blocks.staff_user_id', $user->id)
            ->whereNull('record_conflict_blocks.revoked_at'));
    }

    public function assertLead(User $user, Lead $lead): void
    {
        abort_unless($this->leads(Lead::query(), $user)->whereKey($lead->getKey())->exists(), 404);
    }

    public function assertHousehold(User $user, Household $household): void
    {
        abort_unless($this->households(Household::query(), $user)->whereKey($household->getKey())->exists(), 404);
    }

    public function assertCaregiver(User $user, ?Caregiver $caregiver): void
    {
        abort_unless($caregiver && $this->caregivers(Caregiver::query(), $user)->whereKey($caregiver->getKey())->exists(), 404);
    }

    public function mayAssignRecords(User $user): bool
    {
        return in_array($this->roleSlug($user), ['platform-superuser', 'om'], true);
    }

    public function canViewSensitiveRecipientData(User $user): bool
    {
        return $this->canViewSensitiveData($user);
    }

    public function canViewSensitiveData(User $user): bool
    {
        if ($user->isPlatformSuperuser()) {
            return true;
        }

        // The SRS prohibits the System Administrator from sensitive client data,
        // even if an administrative grant is accidentally assigned to the role.
        if ($this->roleSlug($user) === 'sa') {
            return false;
        }

        return $user->hasPermission('sensitive_data_view');
    }
}
