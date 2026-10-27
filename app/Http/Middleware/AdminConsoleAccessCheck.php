<?php

namespace App\Http\Middleware;

use App\Admin\Models\Role;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminConsoleAccessCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = Role::query()->find(session('role_id'));
        $allowedRoles = ['sa'];
        if ($request->user()->isPlatformSuperuser() || ($role && in_array($role->slug, $allowedRoles, true))) {
            return $next($request);
        }
        abort(401);
    }
}
