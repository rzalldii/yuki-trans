<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        $lastUsername = $request->session()->get('last_login_attempt');
        $lockoutSeconds = null;
        if ($lastUsername) {
            $throttleKey = strtolower($lastUsername) . '|' . $request->ip();
            if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
                $lockoutSeconds = RateLimiter::availableIn($throttleKey);
            }
        }
        return view('auth.login', compact('lockoutSeconds'));
    }

    public function login(Request $request)
    {
        $request->session()->put('last_login_attempt', $request->input('username'));
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        $throttleKey = strtolower($request->input('username')) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            AuditLog::record('login_blocked', null, null, ['attempted_username' => $request->input('username')]);
            return back()->withErrors([
                'username' => "Too many failed login attempts. Please try again in {$seconds} seconds.",
            ])->withInput($request->only('username'));
        }
        if (!Auth::attempt($request->only('username', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 180);
            AuditLog::record('login_failed', null, null, ['attempted_username' => $request->input('username')]);
            return back()->withErrors([
                'username' => 'Invalid credentials. The username or password you entered is incorrect.',
            ])->withInput($request->only('username'));
        }
        RateLimiter::clear($throttleKey);
        $request->session()->forget('last_login_attempt');
        $request->session()->regenerate();
        AuditLog::record('login', auth()->id());
        return redirect()->intended(route('dashboard'))->with('toast', [
            'icon' => 'success',
            'title' => 'Login successful.'
        ]);
    }

    public function logout(Request $request)
    {
        AuditLog::record('logout', auth()->id());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('toast', [
            'icon' => 'success',
            'title' => 'Logout successful.'
        ]);
    }
}
