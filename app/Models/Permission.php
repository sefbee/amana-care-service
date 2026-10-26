<?php

namespace App\Models;

use App\Traits\CommonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string|null $slug
 * @property int $is_active
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read mixed $controller_name
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 *
 * @mixin \Eloquent
 */
class Permission extends Model
{
    use CommonAttributes;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'controller', 'is_active', 'description'];

    protected $appends = ['controller_name'];

    public function getControllerNameAttribute()
    {
        return \Str::of($this->name)->explode('@')[0];
    }
}
