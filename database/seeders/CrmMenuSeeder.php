<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class CrmMenuSeeder extends Seeder
{
    public function run(): void
    {
        // ── CRM ─────────────────────────────────────────────────────
        $crm = $this->menu('CRM', null, 'people', 20);
        $this->menu('Leads',      'leads.index',      null, 1, $crm->id);
        $this->menu('Households', 'households.index', null, 2, $crm->id);
    }

    private function menu(string $title, ?string $routeName, ?string $icon, int $order, ?int $parentId = null): Menu
    {
        $menu = $routeName
            ? Menu::withTrashed()
                ->where('route_name', $routeName)
                ->where('parent_menu_id', $parentId)
                ->first()
            : null;

        $menu ??= Menu::withTrashed()
            ->where('title', $title)
            ->where('parent_menu_id', $parentId)
            ->first();

        $menu ??= Menu::withTrashed()->firstOrNew([
            'title'          => $title,
            'parent_menu_id' => $parentId,
        ]);

        $menu->fill([
            'title'        => $title,
            'route_name'   => $routeName,
            'menu_icon'    => $icon,
            'menu_order'   => $order,
            'is_active'    => true,
        ]);

        if ($menu->trashed()) {
            $menu->restore();
        }

        $menu->save();

        return $menu;
    }
}
