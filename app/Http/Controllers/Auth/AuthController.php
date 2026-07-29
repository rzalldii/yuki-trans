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
            $lockKey = $this->lockKey($lastUsername, $request->ip());
            if (RateLimiter::tooManyAttempts($lockKey, 1)) {
                $lockoutSeconds = RateLimiter::availableIn($lockKey);
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
        $username = $request->input('username');
        $attemptKey = $this->attemptKey($username, $request->ip());
        $lockKey = $this->lockKey($username, $request->ip());
        if (RateLimiter::tooManyAttempts($lockKey, 1)) {
            AuditLog::record('login_blocked', null, null, [
                'attempted_username' => $username,
            ]);
            return back()->withInput($request->only('username', 'remember'));
        }
        $remember = $request->boolean('remember');
        $password = $request->input('password');
        if (!Auth::attempt($request->only('username', 'password'), $remember)) {
            $attempts = RateLimiter::hit($attemptKey, 180);
            if ($attempts >= 3) {
                RateLimiter::hit($lockKey, 180);
                RateLimiter::clear($attemptKey);
                AuditLog::record('login_blocked', null, null, [
                    'attempted_username' => $username,
                ]);
                return back()->withInput($request->only('username', 'remember'));
            }
            AuditLog::record('login_failed', null, null, [
                'attempted_username' => $username,
            ]);
            return back()->withErrors([
                'username' => 'Invalid credentials. The username or password you entered is incorrect.',
            ])->withInput($request->only('username', 'remember'));
        }
        RateLimiter::clear($attemptKey);
        RateLimiter::clear($lockKey);
        $request->session()->forget('last_login_attempt');
        $request->session()->regenerate();
        Auth::logoutOtherDevices($password);
        auth()->user()->forceFill([
            'remember_token_created_at' => $remember ? now() : null,
        ])->save();
        AuditLog::record('login', auth()->id());
        return redirect()->intended(route('dashboard'))->with('toast', [
            'icon' => 'success',
            'title' => 'Login successful.',
        ]);
    }

    public function logout(Request $request)
    {
        if ($user = auth()->user()) {
            AuditLog::record('logout', $user->id);
            $user->forceFill([
                'remember_token' => null,
                'remember_token_created_at' => null,
            ])->save();
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('toast', [
            'icon' => 'success',
            'title' => 'Logout successful.',
        ]);
    }

    private function attemptKey(string $username, string $ip): string
    {
        return 'login-attempt:' . strtolower($username) . '|' . $ip;
    }

    private function lockKey(string $username, string $ip): string
    {
        return 'login-lock:' . strtolower($username) . '|' . $ip;
    }
}