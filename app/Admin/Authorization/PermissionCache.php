<?php

namespace App\Admin\Authorization;

use Illuminate\Support\Facades\Cache;

final class PermissionCache
{
    private const EPOCH_KEY = 'authorization.permission_epoch';

    public static function key(int $userId, int $roleId): string
    {
        return 'user_permissions_v3_'.self::epoch().'_'.$userId.'_'.$roleId;
    }

    public static function invalidate(): void
    {
        self::epoch();
        Cache::increment(self::EPOCH_KEY);
    }

    private static function epoch(): int
    {
        Cache::add(self::EPOCH_KEY, 1, now()->addYears(10));

        return (int) Cache::get(self::EPOCH_KEY, 1);
    }
}
