<?php

namespace App\Admin\Authorization;

use App\Admin\Models\Permission;
use App\Admin\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait PermissionTrait
{
    public function hasPermission($permission): bool
    {
        $user = $this;
        if ($user->isPlatformSuperuser()) {
            return true;
        }

        $roleId = (int) session('role_id', $user->primary_role_id);
        if (! $roleId || ! $this->hasActiveRoleAssignment((int) $user->id, $roleId)) {
            return false;
        }

        $permissions = Cache::remember(PermissionCache::key((int) $user->id, $roleId), 60, function () use ($roleId) {
            $role = Role::query()->where('is_active', true)
                ->with(['permissions' => fn ($query) => $query->where('permissions.is_active', true)])
                ->find($roleId);

            return $role?->permissions ?? collect();
        });

        return $permissions->pluck('name')->contains($permission) || $permissions->pluck('slug')->contains($permission);
    }

    public function permissions()
    {
        $roleId = (int) session('role_id', $this->primary_role_id);
        if (! $roleId || ! $this->hasActiveRoleAssignment((int) $this->id, $roleId)) {
            return Permission::query()->whereRaw('1 = 0');
        }

        $role = Role::query()->where('is_active', true)->find($roleId);

        return $role?->permissions() ?? Permission::query()->whereRaw('1 = 0');
    }

    private function hasActiveRoleAssignment(int $userId, int $roleId): bool
    {
        return DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $userId)
            ->where('role_user.role_id', $roleId)
            ->where('role_user.is_active', true)
            ->where('roles.is_active', true)
            ->whereNull('roles.deleted_at')
            ->exists();
    }
}
