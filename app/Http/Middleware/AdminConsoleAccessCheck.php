<?php

namespace App\Http\Middleware;

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
        $role = session('role') ?? '';
        $allowed_roles = ['admin'];
        if ($request->user()->is_superuser || ($role && in_array($role->slug, $allowed_roles))) {
            return $next($request);
        }
        abort(401);
    }
}
