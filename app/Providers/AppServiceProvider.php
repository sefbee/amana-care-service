<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Response::macro('success', function (array|object|null $data = null, string $message = 'Success', int|string $code = 200) {

            // check if data array or object is empty
            if (gettype($data) == 'array' || gettype($data) == 'object') {
                $data = empty($data) ? null : $data;
            }

            $message = $data ? $message : 'No data found.';

            return response()->json([
                'message' => $message,
                'data' => $data,
            ], intval($code));
        });

        Response::macro('error', function (string $message = 'Error', ?object $errors = null, int|string $code = 500) {
            $allowed_codes = [204, 400, 401, 403, 404, 405, 406, 408, 422, 429, 500, 501, 502, 503, 504, 505];

            if (! in_array($code, $allowed_codes)) {
                $code = 500;
                $message = 'Internal server error.';
            }

            if (gettype($errors) == 'object') {
                $errors = $errors->isEmpty() ? null : $errors;
            }

            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], intval($code));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
        Model::preventAccessingMissingAttributes(! app()->isProduction());

        DB::prohibitDestructiveCommands(app()->isProduction());
        Date::use(CarbonImmutable::class);

        /* ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        }); */

        Gate::before(function (User $user, $ability) {
            // dd($ability);

            return $user->hasPermission($ability);
        });
    }
}
