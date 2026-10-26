<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // return $next($request);
        $response = $next($request);

        if ($request->method() !== 'GET') {
            $logName = $request->method() ?? 'DEFAULT';

            $form_data = $request->except(['password', 'password_confirmation', 'password_current', '_token', '_method']);

            $user = auth()->check() ? auth()->user()->name_with_userid : 'Guest';

            $role = session()->has('role') ? session()->get('role')->title : 'n/a';

            activity($logName)
                ->withProperties([
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'method' => $request->method(),
                    'agent' => $request->header('user-agent'),
                    'user' => [
                        'name' => $user,
                        'user_type' => $role,
                    ],
                    'form_data' => $form_data,
                ])
                ->log($request->fullUrl());
        }

        return $response;
    }
}
