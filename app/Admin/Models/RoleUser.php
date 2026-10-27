<?php

namespace App\Admin\Models;

use App\Admin\Authorization\PermissionCache;
use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    protected $table = 'role_user';

    public $timestamps = true;

    protected $fillable = [
        'role_id',
        'user_id',
        'is_primary',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => PermissionCache::invalidate());
        static::deleted(fn () => PermissionCache::invalidate());
    }
}
