<?php

namespace App\Http\Controllers\AdminConsole;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserRoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()->get();

        return view('admin-console.user_roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'string|required|max:100',
            'is_active' => 'integer|required',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['title']);
            Role::create($validated);

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

        try {
            $validated['slug'] = Str::slug($validated['title']);
            $role = Role::findOrFail($user_type);
            $role->title = $validated['title'];
            $role->is_active = $validated['is_active'];
            $role->save();

            return back()->withSuccess('User role updated successfully');
        } catch (\Exception $e) {
            return back()->withError('User role update failed');
        }
    }

    public function destroy($user_role)
    {
        try {
            Role::find($user_role)->delete();

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
            'admin-console.user_roles.config',
            compact('userRole', 'menus', 'permissions', 'user_type_menus', 'user_type_permissions')
        );
    }

    public function updateMenus(Request $request, $user_role)
    {
        DB::beginTransaction();
        try {
            $role = Role::findOrFail($user_role);
            $role->menus()->detach();
            $role->menus()->attach($request->role_menus);

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
        DB::beginTransaction();
        try {
            $role = Role::findOrFail($user_role);
            $role->permissions()->detach();
            $role->permissions()->attach($request->role_permissions);

            DB::commit();

            flash()->success('Success! Successfully updated!');
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error('Oops! Something went wrong!');
        }

        return back();
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

        return view('admin-console.system_info', compact('sys_info'));
    }
}
