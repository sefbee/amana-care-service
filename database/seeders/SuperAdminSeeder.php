<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::withTrashed()->updateOrCreate(
            ['slug' => 'admin'],
            ['title' => 'Admin', 'is_active' => true],
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
            'is_superuser' => true,
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

        RoleUser::updateOrCreate(
            ['user_id' => $user->id, 'role_id' => $role->id],
            ['is_primary' => true, 'is_active' => true, 'released_at' => null],
        );

        $this->menu('Dashboard', 'dashboard', 'speedometer', 1);
        $this->menu('Employee', 'users.index', 'users', 10);
        $adminConsole = $this->menu('Admin Console', null, 'lock', 100);
        $this->menu('Menus', 'menus.index', null, 1, $adminConsole->id);
        $this->menu('User Roles', 'user-roles.index', null, 2, $adminConsole->id);
        $this->menu('Permissions', 'permissions.index', null, 3, $adminConsole->id);
    }

    private function menu(string $title, ?string $routeName, ?string $icon, int $order, ?int $parentId = null): Menu
    {
        $menu = $routeName
            ? Menu::withTrashed()
                ->where('route_name', $routeName)
                ->where('parent_menu_id', $parentId)
                ->first()
            : null;
        $menu ??= Menu::withTrashed()->where('title', $title)
            ->where('parent_menu_id', $parentId)
            ->first();
        $menu ??= Menu::withTrashed()->firstOrNew([
            'title' => $title,
            'parent_menu_id' => $parentId,
        ]);
        $menu->fill([
            'title' => $title,
            'route_name' => $routeName,
            'menu_icon' => $icon,
            'menu_order' => $order,
            'is_active' => true,
        ]);
        if ($menu->trashed()) {
            $menu->restore();
        }
        $menu->save();

        return $menu;
    }
}
