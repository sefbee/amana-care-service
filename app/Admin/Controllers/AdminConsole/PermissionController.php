<?php

namespace App\Admin\Controllers\AdminConsole;

use App\Admin\Attributes\PermissionAttr;
use App\Http\Controllers\Controller;
use App\Admin\Models\Permission;
use App\Admin\Authorization\PermissionCache;
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
            $controller_name = $this->resolveControllerClass($permission->controller);
            if (! class_exists($controller_name)) {
                $permissions_not_exist[] = $permission;
            }
        }

        return view('admin.identity.admin-console.permissions.index', compact('permissions', 'permissions_not_exist'));
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

                            if (! Str::startsWith($action_name, ['Auth', 'AdminConsole', 'Api', 'Dashboard', 'CmsPublicController']) && $class_methods->contains($method_name)) {
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

            DB::transaction(function () use ($permissions): void {
                if (count($permissions)) {
                    Permission::insert($permissions);
                }
                activity('authorization')->causedBy(request()->user())
                    ->withProperties(['created_permission_count' => count($permissions)])
                    ->log('Route permissions synchronized');
            });

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
        $class_name = $this->resolveControllerClass($controller_name);
        $class = new \ReflectionClass($class_name);
        $class_methods = $class->getMethods();
        $class_methods = collect($class_methods)
            ->map(fn ($item) => $item->name)
            ->filter(fn ($item) => $item !== '__construct' && $item !== 'middleware');

        DB::transaction(function () use ($class_methods, $class_name, $controller_name): void {
            foreach ($class_methods as $method_name) {
                $method = new ReflectionMethod($class_name, $method_name);
                $attributes = $method->getAttributes(PermissionAttr::class);
                $description = null;
                if (count($attributes)) {
                    $attributeInstance = $attributes[0]->newInstance();
                    $description = $attributeInstance->description ?? null;
                }

                $perm = Permission::query()
                    ->where('name', "{$controller_name}@{$method_name}")
                    ->first();

                if ($perm) {
                    $perm->update(['description' => $description]);
                } else {
                    $slug = Str::replace('\\', ':', "{$controller_name}@{$method_name}");
                    $slug = Str::snake($slug);
                    $slug = preg_replace('/:_/', '/', $slug);

                    Permission::create([
                        'name' => $controller_name . '@' . $method->name,
                        'slug' => $slug,
                        'controller' => $controller_name,
                        'description' => $description,
                    ]);
                }
            }

            activity('authorization')->causedBy(request()->user())
                ->withProperties(['controller' => $controller_name, 'method_count' => $class_methods->count()])
                ->log('Controller permissions synchronized');
        });

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
            DB::transaction(function () use ($validated, $request): void {
                $permission = Permission::create($validated);
                activity('authorization')->causedBy($request->user())->performedOn($permission)
                    ->withProperties(['permission_id' => $permission->id, 'is_active' => (bool) $permission->is_active])
                    ->log('Permission created');
            });

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
            DB::transaction(function () use ($permission, $validated): void {
                $permission->fill($validated)->save();
                activity('authorization')->causedBy(request()->user())->performedOn($permission)
                    ->withProperties(['permission_id' => $permission->id, 'is_active' => (bool) $permission->is_active])
                    ->log('Permission updated');
            });

            return back()->withSuccess('Permission updated successfully');
        } catch (Exception $e) {
            return back()->withError('Permission update failed');
        }
    }

    public function destroy(Permission $permission)
    {
        try {
            DB::transaction(function () use ($permission): void {
                $permission->delete();
                activity('authorization')->causedBy(request()->user())->performedOn($permission)
                    ->withProperties(['permission_id' => $permission->id])
                    ->log('Permission archived');
            });

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
                activity('authorization')->causedBy(request()->user())
                    ->withProperties(['permission_id' => $permission->id])
                    ->log('Orphaned permission permanently removed');
            });
            PermissionCache::invalidate();

            return back()->withSuccess('Permission deleted successfully');
        } catch (Exception $e) {
            return back()->withError('Permission delete failed');
        }
    }

    private function resolveControllerClass(string $controller): string
    {
        if (str_starts_with($controller, 'Cms')) {
            return 'App\\FrontEnd\\Cms\\Controllers\\'.$controller;
        }

        return 'App\\Admin\\Controllers\\'.$controller;
    }
}
