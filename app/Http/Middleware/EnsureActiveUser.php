<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $activeRoleIds = DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $user->id)
            ->where('role_user.is_active', true)
            ->where('roles.is_active', true)
            ->whereNull('roles.deleted_at')
            ->pluck('roles.id');

        $selectedRoleId = $request->hasSession()
            ? $request->session()->get('role_id', $user->primary_role_id)
            : $user->primary_role_id;

        $hasValidRole = $user->isPlatformSuperuser()
            || ($activeRoleIds->isNotEmpty() && $activeRoleIds->contains((int) $selectedRoleId));

        if (! $user->is_active || ! $hasValidRole) {
            if ($request->hasSession()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $currentToken = $user->currentAccessToken();
            if ($currentToken && method_exists($currentToken, 'delete')) {
                $currentToken->delete();
            }

            abort(401, 'This account or its active role is no longer authorized.');
        }

        return $next($request);
    }
}
