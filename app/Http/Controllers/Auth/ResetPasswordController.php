<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password')->with([
            'request' => $request,
            'token' => $token,
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'token.required' => 'Token reset password wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password baru wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        // Reset password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                // Revoke all sessions
                $this->revokeUserSessions($user);

                event(new PasswordReset($user));
            }
        );

        // Record security event
        activity('password_reset_completed', 'user', null, [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
        ]);

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with([
                'status' => 'Password berhasil direset. Silakan login dengan password baru.',
            ]);
        }

        return back()->withErrors([
            'email' => [__($status)],
        ]);
    }

    protected function revokeUserSessions($user)
    {
        // Invalidate all sessions for this user
        // This is a simplified implementation
        // In production, you might want to use a more sophisticated session management

        // Log out from current session if user is logged in
        if (Auth::check() && Auth::id() === $user->id) {
            Auth::logout();
        }

        // Additional session revocation logic can be added here
        // For example, invalidate all tokens or mark sessions as expired
    }
}
