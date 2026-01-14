<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\CaptchaService;
use Illuminate\Http\Request;

class CaptchaController extends Controller
{
    protected $captchaService;

    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
    }

    public function verify(Request $request)
    {
        $request->validate([
            'captcha_response' => 'required|string',
            'action' => 'nullable|string',
        ], [
            'captcha_response.required' => 'Captcha response wajib diisi',
        ]);

        $isValid = $this->captchaService->verify(
            $request->captcha_response,
            $request->action,
            $request->ip()
        );

        if ($isValid) {
            // Store verification result in session/cache for short term
            $request->session()->put('captcha_verified', now()->timestamp());

            return response()->json([
                'success' => true,
                'message' => 'Captcha verified successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Captcha verification failed',
        ], 422);
    }

    public function getChallenge(Request $request)
    {
        $challenge = $this->captchaService->generateChallenge($request->action ?? 'default');

        return response()->json([
            'success' => true,
            'challenge' => $challenge,
        ]);
    }
}
