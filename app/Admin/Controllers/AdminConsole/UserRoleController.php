<?php

namespace App\Admin\Controllers\AdminConsole;

use App\Http\Controllers\Controller;
use App\Admin\Models\Menu;
use App\Admin\Models\Permission;
use App\Admin\Models\Role;
use App\Admin\Authorization\PermissionCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserRoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()->get();

        return view('admin.identity.admin-console.user_roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'string|required|max:100',
            'is_active' => 'integer|required',
        ]);
        abort_if(in_array(Str::slug($validated['title']), ['sa', 'platform-superuser'], true), 422, 'This system role is reserved and cannot be recreated here.');

        try {
            $validated['slug'] = Str::slug($validated['title']);
            DB::transaction(function () use ($validated, $request): void {
                $role = Role::create($validated);
                activity('authorization')->causedBy($request->user())->performedOn($role)
                    ->withProperties(['role_id' => $role->id, 'role_slug' => $role->slug, 'is_active' => (bool) $role->is_active])
                    ->log('Role created');
            });

            return back()->withSuccess('User role saved successfully');
        } catch (\Exception $e) {
            return back()->withError('User role save failed');
        }
    }

    public function update(Request $request, $user_type)
    {
        $validated = $request->validate([
            'title' => 'string|required|max:100',
            'is_active' => 'integer|required',
        ]);
        $role = Role::findOrFail($user_type);
        $this->assertRoleIsEditable($role);
        abort_if(in_array(Str::slug($validated['title']), ['sa', 'platform-superuser'], true) && Str::slug($validated['title']) !== $role->slug, 422, 'This system role slug is reserved.');

        try {
            DB::transaction(function () use ($validated, $role, $request): void {
                $role->title = $validated['title'];
                $role->slug = Str::slug($validated['title']);
                $role->is_active = $validated['is_active'];
                $role->save();
                activity('authorization')->causedBy($request->user())->performedOn($role)
                    ->withProperties(['role_id' => $role->id, 'is_active' => (bool) $role->is_active])
                    ->log('Role updated');
            });

            return back()->withSuccess('User role updated successfully');
        } catch (\Exception $e) {
            return back()->withError('User role update failed');
        }
    }

    public function destroy($user_role)
    {
        $role = Role::findOrFail($user_role);
        $this->assertRoleIsEditable($role);
        try {
            DB::transaction(function () use ($role): void {
                $role->delete();
                activity('authorization')->causedBy(request()->user())->performedOn($role)
                    ->withProperties(['role_id' => $role->id, 'role_slug' => $role->slug])
                    ->log('Role deleted');
            });

            return back()->withSuccess('User role deleted successfully');
        } catch (\Exception $e) {
            return back()->withError('User type delete failed');
        }
    }

    public function config($user_role)
    {
        $userRole = Role::findOrFail($user_role);
        $menus = Menu::active()
            ->regularMenu()
            ->oldest('menu_order')
            ->get();
        $permissions = Permission::latest('id')->get()->groupBy('controller_name');
        $user_type_menus = DB::table('menu_role')
            ->where('role_id', $userRole->id)
            ->select('menu_id')
            ->get()
            ->pluck('menu_id');

        $user_type_permissions = DB::table('permission_role')
            ->where('role_id', $userRole->id)
            ->select('permission_id')
            ->get()
            ->pluck('permission_id');

        return view(
            'admin.identity.admin-console.user_roles.config',
            compact('userRole', 'menus', 'permissions', 'user_type_menus', 'user_type_permissions')
        );
    }

    public function updateMenus(Request $request, $user_role)
    {
        $role = Role::findOrFail($user_role);
        $this->assertRoleIsEditable($role);
        $validated = $request->validate([
            'role_menus' => ['sometimes', 'array'],
            'role_menus.*' => ['integer', 'distinct', 'exists:menus,id'],
        ]);
        $menuIds = array_map('intval', $validated['role_menus'] ?? []);
        DB::beginTransaction();
        try {
            $previousMenuIds = DB::table('menu_role')->where('role_id', $role->id)->pluck('menu_id')->map(fn ($id) => (int) $id)->all();
            $role->menus()->sync($menuIds);

            activity('authorization')->causedBy($request->user())->performedOn($role)
                ->withProperties([
                    'role_id' => $role->id,
                    'added_menu_ids' => array_values(array_diff($menuIds, $previousMenuIds)),
                    'removed_menu_ids' => array_values(array_diff($previousMenuIds, $menuIds)),
                ])
                ->log('Role navigation grants replaced');
            DB::commit();

            flash()->success('Success! Successfully Updated!');
        } catch (\Exception $e) {
            DB::rollBack();

            flash()->error('Oops! Something went wrong!');
        }

        return back();
    }

    public function updatePermissions(Request $request, $user_role)
    {
        $role = Role::findOrFail($user_role);
        $this->assertRoleIsEditable($role);
        $validated = $request->validate([
            'role_permissions' => ['sometimes', 'array'],
            'role_permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);
        $permissionIds = array_map('intval', $validated['role_permissions'] ?? []);
        DB::beginTransaction();
        try {
            $previousPermissionIds = DB::table('permission_role')->where('role_id', $role->id)->pluck('permission_id')->map(fn ($id) => (int) $id)->all();
            $role->permissions()->sync($permissionIds);

            activity('authorization')->causedBy($request->user())->performedOn($role)
                ->withProperties([
                    'role_id' => $role->id,
                    'added_permission_ids' => array_values(array_diff($permissionIds, $previousPermissionIds)),
                    'removed_permission_ids' => array_values(array_diff($previousPermissionIds, $permissionIds)),
                ])
                ->log('Role permissions replaced');
            DB::commit();
            PermissionCache::invalidate();

            flash()->success('Success! Successfully updated!');
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error('Oops! Something went wrong!');
        }

        return back();
    }

    private function assertRoleIsEditable(Role $role): void
    {
        abort_unless(
            auth()->user()->isPlatformSuperuser() || ! in_array($role->slug, ['sa', 'platform-superuser'], true),
            403,
            'System identity roles can only be changed by a Platform Superuser.'
        );
    }

    public function system_info()
    {
        // Try to get MySQL version safely
        try {
            $mysqlVersion = DB::select('SELECT VERSION() as version')[0]->version ?? 'Not available';
        } catch (\Exception $e) {
            $mysqlVersion = 'Not available';
        }

        // Detect server software (Apache/Nginx)
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $isApache = stripos($serverSoftware, 'Apache') !== false;
        $isNginx = stripos($serverSoftware, 'nginx') !== false;

        $sys_info = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_software' => $isApache ? 'Apache' : ($isNginx ? 'Nginx' : $serverSoftware),
            'mysql_version' => $mysqlVersion,
            'server_os' => php_uname(),
            'server_host' => request()->getHost(),
            'client_ip' => request()->ip(),
            'environment' => app()->environment(),

            'ini_values' => [
                'memory_limit' => ini_get('memory_limit'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time'),
                'max_input_time' => ini_get('max_input_time'),
                'display_errors' => ini_get('display_errors'),
                'error_reporting' => ini_get('error_reporting'),
                'file_uploads' => ini_get('file_uploads'),
            ],

            'enabled_extensions' => get_loaded_extensions(),
        ];

        return view('admin.identity.admin-console.system_info', compact('sys_info'));
    }
}
