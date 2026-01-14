<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\DeviceFingerprintingService;
use App\Services\Auth\RiskAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnhancedLoginController extends Controller
{
    protected $deviceService;

    protected $riskService;

    public function __construct(
        DeviceFingerprintingService $deviceService,
        RiskAssessmentService $riskService
    ) {
        $this->deviceService = $deviceService;
        $this->riskService = $riskService;
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.enhanced-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
            'otp' => 'nullable|string|digits:6',
            'captcha' => 'nullable|string',
        ], [
            'identifier.required' => 'Email, username, atau nomor telepon wajib diisi',
            'password.required' => 'Password wajib diisi',
            'otp.digits' => 'OTP harus 6 digit',
        ]);

        // Rate limiting
        $this->checkTooManyFailedAttempts($request);

        // Device fingerprinting
        $deviceFingerprint = $this->deviceService->fingerprint($request);

        // Risk assessment
        $riskScore = $this->riskService->assessLoginRisk($request, $deviceFingerprint);

        // Require CAPTCHA if risk is high
        if ($riskScore > 0.7 && ! $request->filled('captcha')) {
            throw ValidationException::withMessages([
                'captcha' => ['Risiko login tinggi. Silakan lengkapi CAPTCHA.'],
            ]);
        }

        // Validate CAPTCHA if provided
        if ($request->filled('captcha')) {
            $this->validateCaptcha($request->captcha);
        }

        // Attempt login
        $credentials = $this->credentials($request);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = auth()->user();

            // Check if 2FA is required
            if ($this->requiresTwoFactor($user, $riskScore, $deviceFingerprint)) {
                if (! $request->filled('otp')) {
                    // Send OTP and require it
                    $this->sendTwoFactorCode($user);
                    throw ValidationException::withMessages([
                        'otp' => ['Kode 2FA diperlukan untuk login ini.'],
                    ]);
                }

                // Verify OTP
                if (! $this->verifyTwoFactorCode($user, $request->otp)) {
                    throw ValidationException::withMessages([
                        'otp' => ['Kode 2FA tidak valid.'],
                    ]);
                }
            }

            $request->session()->regenerate();

            // Register device
            $this->deviceService->registerDevice($user->id, $deviceFingerprint);

            // Clear rate limiter
            RateLimiter::clear($this->throttleKey($request));

            // Log successful login
            activity('enhanced_login', 'user', $user->id, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_fingerprint' => $deviceFingerprint,
                'risk_score' => $riskScore,
                'two_factor_used' => $request->filled('otp'),
            ]);

            // Redirect based on role
            return $this->redirectAfterLogin($user);
        }

        // Increment failed attempts
        RateLimiter::hit($this->throttleKey($request), 300);

        throw ValidationException::withMessages([
            'identifier' => ['Kredensial tidak valid.'],
        ]);
    }

    public function logout(Request $request)
    {
        if (auth()->check()) {
            activity('enhanced_logout', 'user', auth()->id());
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', 'Anda telah berhasil logout');
    }

    protected function credentials(Request $request)
    {
        $login = $request->input('identifier');

        // Determine field type
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
        } elseif (preg_match('/^[0-9]+$/', $login)) {
            $field = 'phone';
        } else {
            $field = 'username';
        }

        return [
            $field => $login,
            'password' => $request->input('password'),
        ];
    }

    protected function redirectAfterLogin($user)
    {
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

    protected function requiresTwoFactor($user, $riskScore, $deviceFingerprint)
    {
        // Always require 2FA for admins
        if (in_array($user->user_type, ['admin'])) {
            return true;
        }

        // Require 2FA for high risk
        if ($riskScore > 0.8) {
            return true;
        }

        // Require 2FA for unknown devices
        if (! $this->deviceService->isTrustedDevice($user->id, $deviceFingerprint)) {
            return true;
        }

        return false;
    }

    protected function sendTwoFactorCode($user)
    {
        // Implementation would send SMS/email with 2FA code
        // For now, just log
        \Log::info('2FA code sent to user', ['user_id' => $user->id]);
    }

    protected function verifyTwoFactorCode($user, $code)
    {
        // Implementation would verify the 2FA code
        // For now, accept any 6-digit code
        return strlen($code) === 6 && is_numeric($code);
    }

    protected function validateCaptcha($captchaResponse)
    {
        // Implementation would validate with reCAPTCHA service
        // For now, just check if not empty
        if (empty($captchaResponse)) {
            throw ValidationException::withMessages([
                'captcha' => ['CAPTCHA tidak valid.'],
            ]);
        }
    }

    protected function checkTooManyFailedAttempts(Request $request)
    {
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'identifier' => ["Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik."],
            ]);
        }
    }

    protected function throttleKey(Request $request)
    {
        return Str::lower($request->input('identifier')).'|'.$request->ip();
    }
}
