<?php

namespace App\Http\Controllers\AdminConsole;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['admin_console_access']);
    }

    /* public static function middleware(): array
    {
        return [
            'admin_console_access',
        ];
    } */

    public function index()
    {
        $menus = Menu::query()
            ->with('parent_menu')
            ->withCount('sub_menus')
            ->regularMenu()
            ->get();

        return view('admin-console.menus.index', compact('menus'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'string|required|max:100',
            'route_name' => 'string|nullable|max:200',
            'menu_icon' => 'string|nullable|max:50',
            'menu_order' => 'integer|nullable',
            'parent_menu_id' => 'integer|nullable',
            'is_active' => 'integer|required',
        ]);
        try {
            $menu_order = $request->menu_order;

            if (! $menu_order) {
                $last_order_menu = Menu::query()
                    ->when($request->parent_menu_id, function ($query) {
                        $query->where('parent_menu_id', request()->parent_menu_id);
                    })
                    ->when(! $request->parent_menu_id, function ($query) {
                        $query->whereNull('parent_menu_id');
                    })
                    ->whereNotIn('title', ['Admin Console', 'Settings', 'Users'])
                    ->latest('menu_order')
                    ->first();

                $menu_order = $last_order_menu ? $last_order_menu->menu_order + 1 : 1;
            }

            $validated['menu_order'] = $menu_order;
            Menu::create($validated);

            // return back()->withSuccess('Menu saved successfully');
            flash()->success('Menu saved successfully');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
            // return back()->withError($e->getMessage());
        }

        return back();
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'title' => 'string|required|max:100',
            'route_name' => 'string|nullable|max:200',
            'menu_icon' => 'string|nullable|max:50',
            'menu_order' => 'integer|nullable',
            'parent_menu_id' => 'integer|nullable',
            'is_active' => 'integer|required',
        ]);

        try {
            Menu::where('id', $menu->id)->update($validated);

            return back()->withSuccess('Menu updated successfully');
        } catch (\Exception $e) {
            return back()->withError('Menu update failed');
        }
    }

    public function destroy(Menu $menu)
    {
        try {
            $menu->delete();

            return back()->withSuccess('Menu deleted successfully');
        } catch (\Exception $e) {
            return back()->withError('Menu delete failed');
        }
    }
}
