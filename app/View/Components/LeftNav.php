<?php

namespace App\View\Components;

use App\Admin\Models\Menu;
use App\Admin\Models\Role;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class LeftNav extends Component
{
    public $menus;

    public $mm; // main menu

    public $sm; // sub menu

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->menus = $this->getMenusForUser();

        $this->mm = request()->query('mm', '');
        $this->sm = request()->query('sm', '');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.left-nav');
    }

    public function getMenusForUser()
    {
        $user = auth()->user();

        // return Cache::remember("menus_{$user->id}", 60, function () use ($user) {
        if ($user->isPlatformSuperuser()) {
            $menus = Menu::active()->orderBy('menu_order', 'asc')->get();
        } else {
            $role = Role::query()->find(session('role_id'));
            $menus = $role ? $role->menus()->active()->orderBy('menu_order', 'asc')->get() : [];
        }

        $preparedData = collect([]);
        foreach ($menus as $menu) {
            if (! $menu->parent_menu_id) {
                $preparedData->push($menu);
                $menu->main_menu = true;

                $sub_menus = collect([]);
                foreach ($menus as $sub_menu) {
                    if ($menu->id === $sub_menu->parent_menu_id) {
                        $sub_menus->push($sub_menu);
                    }
                }
                $menu->sub_menus = $sub_menus;
            }
        }

        return $preparedData;
        // });
    }
}
