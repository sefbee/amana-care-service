<?php

namespace Database\Seeders\Admin;

use App\Admin\Models\Role;
use App\Admin\Models\RoleUser;
use App\Admin\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::withTrashed()->updateOrCreate(
            ['slug' => 'platform-superuser'],
            ['title' => 'Platform Superuser', 'is_active' => true],
        );
        if ($role->trashed()) {
            $role->restore();
        }

        $settings = config('starter.superadmin');
        $userId = $settings['userid'];
        $user = User::withTrashed()->firstOrNew(['userid' => $userId]);
        $isNewUser = ! $user->exists;
        $password = $settings['password'];

        if ($isNewUser && blank($password)) {
            throw new \RuntimeException('Set a strong SUPERADMIN_PASSWORD in the environment before seeding.');
        }

        $user->fill([
            'name' => $settings['name'],
            'email' => $settings['email'],
            'primary_role_id' => $role->id,
            'updated_by' => $user->id ?? 0,
            'is_active' => true,
            'is_platform_superuser' => true,
            'can_access_admin_panel' => true,
        ]);
        if ($user->trashed()) {
            $user->restore();
        }
        if ($isNewUser) {
            $user->password = Hash::make($password);
            $user->is_password_changed = false;
        }
        $user->save();

        // The users table requires updated_by; for a newly created admin, point it to itself.
        if ($user->updated_by === 0) {
            $user->updated_by = $user->id;
            $user->save();
        }

        // The seeded PSU account must not retain a legacy SA role assignment.
        RoleUser::query()->where('user_id', $user->id)->delete();
        RoleUser::updateOrCreate(
            ['user_id' => $user->id, 'role_id' => $role->id],
            ['is_primary' => true, 'is_active' => true, 'released_at' => null],
        );

    }
}
