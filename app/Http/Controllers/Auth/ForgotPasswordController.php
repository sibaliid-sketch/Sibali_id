<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
        ]);

        // Rate limiting
        $this->checkTooManyRequests($request);

        // Send password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Record security event
        activity('password_reset_requested', 'system', null, [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            // Clear rate limiter on success
            RateLimiter::clear($this->throttleKey($request));

            return back()->with([
                'status' => 'Link reset password telah dikirim ke email Anda.',
            ]);
        }

        // Increment failed attempts
        RateLimiter::hit($this->throttleKey($request), 300); // 5 minutes

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    protected function checkTooManyRequests(Request $request)
    {
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, 3)) { // Max 3 requests per 5 minutes
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => ["Terlalu banyak permintaan reset password. Silakan coba lagi dalam {$seconds} detik."],
            ]);
        }
    }

    protected function throttleKey(Request $request)
    {
        return Str::lower($request->input('email')).'|'.$request->ip();
    }
}
