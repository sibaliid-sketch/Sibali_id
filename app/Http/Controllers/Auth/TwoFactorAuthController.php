<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorAuthController extends Controller
{
    protected $twoFactorService;

    public function __construct(TwoFactorAuthService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
        $this->middleware('auth');
    }

    public function showSetupForm()
    {
        $user = Auth::user();

        if ($this->twoFactorService->isEnabled($user)) {
            return redirect()->route('profile')->with('info', '2FA sudah diaktifkan');
        }

        $qrCode = $this->twoFactorService->generateQRCode($user);

        return view('auth.two-factor.setup', compact('qrCode'));
    }

    public function setup(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6',
        ], [
            'code.required' => 'Kode verifikasi wajib diisi',
            'code.digits' => 'Kode verifikasi harus 6 digit',
        ]);

        $user = Auth::user();

        if ($this->twoFactorService->enable($user, $request->code)) {
            activity('2fa_enabled', 'user', $user->id);

            return redirect()->route('profile')->with('success', '2FA berhasil diaktifkan');
        }

        return back()->withErrors(['code' => 'Kode verifikasi tidak valid']);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'token' => 'required|string|digits:6',
        ], [
            'token.required' => 'Token 2FA wajib diisi',
            'token.digits' => 'Token 2FA harus 6 digit',
        ]);

        $user = Auth::user();

        if ($this->twoFactorService->verify($user, $request->token)) {
            // Mark 2FA as verified for this session
            session(['2fa_verified' => true]);

            activity('2fa_verified', 'user', $user->id);

            return response()->json([
                'success' => true,
                'message' => '2FA verified successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid 2FA token',
        ], 422);
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (! password_verify($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password tidak valid']);
        }

        $this->twoFactorService->disable($user);

        activity('2fa_disabled', 'user', $user->id);

        return redirect()->route('profile')->with('success', '2FA berhasil dinonaktifkan');
    }

    public function showRecoveryCodes()
    {
        $user = Auth::user();

        if (! $this->twoFactorService->isEnabled($user)) {
            return redirect()->route('profile')->with('error', '2FA belum diaktifkan');
        }

        $recoveryCodes = $this->twoFactorService->getRecoveryCodes($user);

        return view('auth.two-factor.recovery-codes', compact('recoveryCodes'));
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (! password_verify($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password tidak valid']);
        }

        $recoveryCodes = $this->twoFactorService->regenerateRecoveryCodes($user);

        activity('2fa_recovery_codes_regenerated', 'user', $user->id);

        return view('auth.two-factor.recovery-codes', compact('recoveryCodes'))
            ->with('success', 'Recovery codes berhasil diregenerasi');
    }
}
