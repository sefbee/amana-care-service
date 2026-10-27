<?php

use App\Http\Middleware\LogActivity;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, //TODO: Check it properly because csrf token mismatch here
        ]);
        $middleware->api(append: [SecurityHeaders::class]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'admin_console_access' => \App\Http\Middleware\AdminConsoleAccessCheck::class,
            'role_permission_check' => \App\Http\Middleware\RolePermissionCheck::class,
            'active_user' => \App\Http\Middleware\EnsureActiveUser::class,
            'platform_superuser_access' => \App\Http\Middleware\PlatformSuperuserAccessCheck::class,
        ]);

        // $middleware->append(LogActivity::class);

        $middleware->web(append: [
            LogActivity::class,
            SecurityHeaders::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['national_id']);
    })->create();
