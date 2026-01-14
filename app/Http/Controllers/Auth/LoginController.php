<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email atau username wajib diisi',
            'password.required' => 'Password wajib diisi',
        ]);

        // Rate limiting
        $this->checkTooManyFailedAttempts($request);

        // Attempt login with email or phone
        $credentials = $this->credentials($request);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            // Clear rate limiter
            RateLimiter::clear($this->throttleKey($request));

            // Log successful login
            activity('user_login', 'user', auth()->id(), [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Redirect based on user type
            return $this->redirectAfterLogin();
        }

        // Increment failed attempts
        RateLimiter::hit($this->throttleKey($request), 300); // 5 minutes

        throw ValidationException::withMessages([
            'email' => ['Email/username atau password salah.'],
        ]);
    }

    public function logout(Request $request)
    {
        // Log logout activity
        if (auth()->check()) {
            activity('user_logout', 'user', auth()->id());
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', 'Anda telah berhasil logout');
    }

    protected function credentials(Request $request)
    {
        $login = $request->input('email');

        // Check if login is email or phone
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        return [
            $field => $login,
            'password' => $request->input('password'),
        ];
    }

    protected function redirectAfterLogin()
    {
        $user = auth()->user();

        $redirects = [
            'student' => route('student.dashboard'),
            'parent' => route('parent.dashboard'),
            'teacher' => route('teacher.dashboard'),
            'admin' => route('admin.dashboard'),
            'staff' => route('staff.dashboard'),
        ];

        $route = $redirects[$user->user_type] ?? route('home');

        return redirect()->intended($route)
            ->with('success', 'Selamat datang, '.$user->name.'!');
    }

    protected function checkTooManyFailedAttempts(Request $request)
    {
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => ["Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik."],
            ]);
        }
    }

    protected function throttleKey(Request $request)
    {
        return Str::lower($request->input('email')).'|'.$request->ip();
    }
}
