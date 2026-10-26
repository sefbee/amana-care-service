<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();
        $roles = $user->roles;

        if ($roles->count()) {
            $primary_role = $user->roles()->where('is_primary', 1)->first();
            session(['roles' => $roles]);
            session(['role' => $primary_role]);
            session(['role_id' => $primary_role->id]);
        }

        $user->last_login = now();
        $user->save();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Cache::forget('user_permissions_' . auth()->user()->id);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function fakeLogin(Request $request): RedirectResponse
    {
        if (app()->environment('local')) {
            $user = User::query()
                ->where('userid', $request->userid)
                ->where('is_active', 1)
                ->firstOrFail();

            $roles = $user->roles;
            if ($roles->count()) {
                $primary_role = $user->roles()->where('is_primary', 1)->first();
                session(['roles' => $roles]);
                session(['role' => $primary_role]);
                session(['role_id' => $primary_role->id]);
            }

            $user->last_login = now();
            $user->save();

            Auth::login($user);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        abort(404);
    }
}
