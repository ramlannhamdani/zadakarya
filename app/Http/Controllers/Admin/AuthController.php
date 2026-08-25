<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** 5 percobaan gagal per email+IP → kunci 1 menit. */
    private const MAX_PER_ACCOUNT = 5;

    private const ACCOUNT_DECAY = 60;

    /** 20 percobaan gagal per IP (email apa pun) → kunci 15 menit. */
    private const MAX_PER_IP = 20;

    private const IP_DECAY = 900;

    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $accountKey = 'login:'.Str::lower($credentials['email']).'|'.$request->ip();
        $ipKey = 'login-ip:'.$request->ip();

        foreach ([[$accountKey, self::MAX_PER_ACCOUNT], [$ipKey, self::MAX_PER_IP]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $seconds = RateLimiter::availableIn($key);

                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."]);
            }
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($accountKey, self::ACCOUNT_DECAY);
            RateLimiter::hit($ipKey, self::IP_DECAY);
            Log::warning('Login admin gagal', ['email' => $credentials['email'], 'ip' => $request->ip()]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        RateLimiter::clear($accountKey);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
