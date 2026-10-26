<?php

namespace App\Models;

use App\Traits\CommonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property string|null $route_name
 * @property string|null $menu_url
 * @property string|null $menu_icon
 * @property int $menu_order
 * @property int|null $parent_menu_id
 * @property int $is_active
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read mixed $published
 * @property-read mixed $status
 * @property-read Menu|null $parent_menu
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Menu> $sub_menus
 * @property-read int|null $sub_menus_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu regularMenu()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereMenuIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereMenuOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereMenuUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereParentMenuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereRouteName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Menu extends Model
{
    use CommonAttributes;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['title', 'route_name', 'menu_icon', 'menu_order', 'parent_menu_id', 'is_active'];

    public function parent_menu()
    {
        return $this->belongsTo(Menu::class, 'parent_menu_id', 'id');
    }

    public function sub_menus()
    {
        return $this->hasMany(Menu::class, 'parent_menu_id', 'id');
    }

    public function scopeRegularMenu($query)
    {
        return $query->whereNotIn('title', ['Admin Console', 'Menus', 'User Roles', 'Permission']);
    }
}
