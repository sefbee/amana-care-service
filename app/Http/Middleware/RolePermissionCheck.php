<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolePermissionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $action = \Route::current()->action['controller'];
        $action_name = explode('Controllers\\', $action)[1];
        if (! $user->hasPermission($action_name)) {
            abort(401, 'You do not have acccess to this page');
        }

        return $next($request);
    }
}
