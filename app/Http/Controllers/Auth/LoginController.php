<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);

            $user = Auth::user();

            // Block inactive users
            if ($user->status !== UserStatus::ACTIVE) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the administrator.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            /** @var \App\Models\User $user */
            $user = Auth::user();

            activity('auth')
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent()])
                ->log('logged in');

            if ($user->department_id) {
                return redirect()->intended(route('admin.my-dashboard'));
            }

            if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
                return redirect()->intended(route('admin.dashboard'));
            }

            return redirect()->intended(route('admin.my-dashboard'));
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        activity('auth')
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('logged out');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
