<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (auth()->check()) {
            if (auth()->user()->is_super_admin) {
                return redirect()->route('super.dashboard');
            }
            if (auth()->user()->canUseAdminPanel()) {
                return redirect()->route('admin.dashboard');
            }
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 5 wrong passwords per email + IP, then a short lock-out.
        $throttleKey = 'admin-login:' . \Illuminate\Support\Str::lower($request->input('email')) . '|' . $request->ip();

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Too many login attempts. Try again in {$seconds} seconds.",
            ])->withInput($request->only('email'));
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
            $user = auth()->user();

            if ($user->is_banned) {
                Auth::logout();
                return back()->withErrors(['email' => 'This account has been disabled.'])->withInput($request->only('email'));
            }

            // Super admin — go to super admin panel
            if ($user->is_super_admin) {
                $request->session()->regenerate();
                return redirect()->route('super.dashboard');
            }

            // Shop owner/admin, or staff with a role
            if (! $user->canUseAdminPanel()) {
                Auth::logout();
                return back()->withErrors(['email' => 'Unauthorized access.'])->withInput($request->only('email'));
            }

            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->withInput($request->only('email', 'remember'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
