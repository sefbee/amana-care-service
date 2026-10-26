<?php

namespace App\Permissions;

use Illuminate\Support\Facades\Cache;

trait PermissionTrait
{
    public function hasPermission($permission): bool
    {
        $user = $this;
        if ($user->is_superuser) {
            return true;
        }

        $permissions = Cache::remember('user_permissions_' . $user->id, 60, function () {
            $role = session('role');

            return $role->permissions;
        });

        return $permissions->pluck('name')->contains($permission) || $permissions->pluck('slug')->contains($permission);
    }

    public function permissions()
    {
        $role = session('role');

        return $role->permissions();
    }
}
