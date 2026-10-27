<?php

namespace App\Http\Middleware;

use App\Admin\Models\Role;
use Illuminate\Database\Eloquent\Model;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Role models were previously serialized into sessions under their old namespace.
        if ($request->hasSession()) {
            $request->session()->forget(['role', 'roles']);
        }

        $userModel = $request->user();
        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            if ($userModel?->isPlatformSuperuser()) {
                $this->auditPlatformSuperuserRequest($request, 'failed');
            }

            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : null;
            if ($userModel && in_array($status, [401, 403, 404], true)) {
                $this->auditDeniedRequest($request, $userModel, $status);
            }

            throw $exception;
        }

        if ($userModel?->isPlatformSuperuser()) {
            $this->auditPlatformSuperuserRequest($request, 'completed');
        }

        if (in_array($response->getStatusCode(), [401, 403, 404], true) && $userModel) {
            $this->auditDeniedRequest($request, $userModel, $response->getStatusCode());
        }

        if ($request->method() !== 'GET') {
            $logName = $request->method() ?? 'DEFAULT';

            $formFields = array_values(array_diff(array_keys($request->all()), ['password', 'password_confirmation', 'password_current', '_token', '_method']));

            $user = auth()->check() ? auth()->user()->name_with_userid : 'Guest';

            $role = $userModel?->isPlatformSuperuser()
                ? 'Platform Superuser'
                : (Role::query()->find(session('role_id') ?? $userModel?->primary_role_id)?->title ?? 'n/a');

            activity($logName)
                ->withProperties([
                    'ip' => $request->ip(),
                    'method' => $request->method(),
                    'agent' => $request->header('user-agent'),
                    'user' => [
                        'name' => $user,
                        'user_type' => $role,
                    ],
                    'form_fields' => $formFields,
                ])
                ->log($request->method().' '.($request->route()?->getName() ?? 'unmatched-route'));
        }

        return $response;
    }

    private function auditPlatformSuperuserRequest(Request $request, string $outcome): void
    {
        $actor = $request->user();
        if (! $actor) {
            return;
        }

        activity('platform_superuser_access')
            ->causedBy($actor)
            ->withProperties([
                'route' => $request->route()?->getName(),
                'action' => $request->route()?->getActionName(),
                'method' => $request->method(),
                'record_parameters' => collect($request->route()?->parameters() ?? [])->map(function ($value): mixed {
                    if ($value instanceof Model) {
                        return ['type' => class_basename($value), 'id' => $value->getKey()];
                    }

                    return is_scalar($value) ? $value : get_debug_type($value);
                })->all(),
                'ip' => $request->ip(),
                'outcome' => $outcome,
            ])
            ->log('Platform Superuser request '.$outcome);
    }

    private function auditDeniedRequest(Request $request, Model $actor, int $status): void
    {
        activity('access_denied')
            ->causedBy($actor)
            ->withProperties([
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'status' => $status,
                'ip' => $request->ip(),
            ])
            ->log('Authenticated request denied');
    }
}
