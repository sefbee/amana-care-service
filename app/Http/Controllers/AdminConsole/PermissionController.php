<?php

namespace App\Http\Controllers\AdminConsole;

use App\Attributes\PermissionAttr;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionMethod;

class PermissionController extends Controller
{
    /* public static function middleware(): array
    {
        return [
            'admin_console_access',
        ];
    } */

    public function index()
    {
        $permissions = Permission::latest('id')->get();

        $permissions_not_exist = [];
        foreach ($permissions as $permission) {
            $controller_name = "App\\Http\\Controllers\\{$permission->controller}";
            if (! class_exists($controller_name)) {
                $permissions_not_exist[] = $permission;
            }
        }

        return view('admin-console.permissions.index', compact('permissions', 'permissions_not_exist'));
    }

    public function syncPermissions()
    {
        $saved_permissions = Permission::withTrashed()->get()->pluck('name')->toArray();
        try {
            $permissions = [];
            foreach (\Route::getRoutes()->getRoutes() as $route) {
                $action = $route->getAction();
                if (array_key_exists('controller', $action)) {
                    // You can also use explode('@', $action['controller']); here
                    // to separate the class name from the method
                    $action_name = $action['controller'];
                    $method_name = explode('@', $action_name)[1] ?? null;

                    $class_name = explode('@', $action_name)[0];

                    if (! class_exists($class_name)) {
                        continue;
                    }

                    $reflection = new \ReflectionClass($class_name);

                    $class_methods = $reflection->getMethods();
                    $class_methods = collect($class_methods)->map(fn ($item) => $item->name);

                    if (substr($action_name, 0, 3) === 'App') {
                        $action_name = explode('Controllers\\', $action_name)[1];

                        if (! in_array($action_name, $saved_permissions)) {
                            $slug = Str::replace('\\', ':', $action_name);
                            $slug = Str::snake($slug);
                            $slug = preg_replace('/:_/', '/', $slug);

                            if (! Str::startsWith($action_name, ['Auth', 'AdminConsole', 'Api', 'Dashboard']) && $class_methods->contains($method_name)) {
                                $description = null;
                                $method = new ReflectionMethod($class_name, $method_name);
                                $attributes = $method->getAttributes(PermissionAttr::class);
                                if (count($attributes)) {
                                    $attributeInstance = $attributes[0]->newInstance();
                                    // $name = $attributeInstance->name ?? null;
                                    $description = $attributeInstance->description ?? null;
                                }

                                $permissions[] = [
                                    'name' => $action_name,
                                    'slug' => $slug,
                                    'controller' => explode('@', $action_name)[0],
                                    'description' => $description,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                        }
                    }
                }
            }

            if (count($permissions)) {
                Permission::insert($permissions);
            }

            // session()->flash('success', 'Successfully synced permissions');
            flash()->success('Successfully synced permissions');
        } catch (\Throwable $th) {
            flash()->error($th->getMessage());
            // session()->flash('error', 'Permission sync failed');
        }

        return back();
    }

    public function sync_controller_permissions(Permission $permission)
    {
        // dd($permission);
        $controller_name = $permission->controller;

        if (! $controller_name) {
            $controller_name = \Str::of($permission->name)->explode('@')[0];
            /* $method_name = \Str::of($permission->name)->explode('@')[1]; */
        }

        // dd($controller_name, $method_name);
        $class_name = 'App\\Http\\Controllers\\' . $controller_name;
        $class = new \ReflectionClass($class_name);
        $class_methods = $class->getMethods();
        $class_methods = collect($class_methods)
            ->map(fn ($item) => $item->name)
            ->filter(fn ($item) => $item !== '__construct' && $item !== 'middleware');

        foreach ($class_methods as $method_name) {
            $method = new ReflectionMethod($class_name, $method_name);
            $attributes = $method->getAttributes(PermissionAttr::class);
            $description = null;
            if (count($attributes)) {
                $attributeInstance = $attributes[0]->newInstance();
                // $name = $attributeInstance->name ?? null;
                $description = $attributeInstance->description ?? null;
            }

            $perm = Permission::query()
                ->where('name', "{$controller_name}@{$method_name}")
                ->first();

            if ($perm) {
                $perm->update([
                    'description' => $description,
                ]);
            } else {
                $slug = Str::replace('\\', ':', "{$controller_name}@{$method_name}");
                $slug = Str::snake($slug);
                $slug = preg_replace('/:_/', '/', $slug);

                Permission::create([
                    'name' => $controller_name . '@' . $method->name,
                    'slug' => $slug,
                    'controller' => $controller_name,
                    'description' => $description,
                    // 'created_at' => now(),
                    // 'updated_at' => now(),
                ]);
            }
        }

        return back()->withSuccess('Controller permission synced successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'string|required|max:100',
            'is_active' => 'required|integer|in:1,0',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['name']);
            Permission::create($validated);

            return back()->withSuccess('Permission saved successfully');
        } catch (Exception $e) {
            return back()->withError('Permission save failed');
        }
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'string|required|max:100',
            'is_active' => 'required|integer|in:1,0',
        ]);

        try {
            Permission::where('id', $permission->id)->update($validated);

            return back()->withSuccess('Permission updated successfully');
        } catch (Exception $e) {
            return back()->withError('Permission update failed');
        }
    }

    public function destroy(Permission $permission)
    {
        try {
            $permission->delete();

            return back()->withSuccess('Permission deleted successfully');
        } catch (Exception $e) {
            return back()->withError('Permission delete failed');
        }
    }

    public function destroy_not_exists(Permission $permission)
    {
        try {
            DB::transaction(function () use ($permission) {
                DB::table('permission_role')->where('permission_id', $permission->id)->delete();

                $permission->forceDelete();
            });

            return back()->withSuccess('Permission deleted successfully');
        } catch (Exception $e) {
            return back()->withError('Permission delete failed');
        }
    }
}
