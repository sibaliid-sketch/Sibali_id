<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialLoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    protected $socialLoginService;

    public function __construct(SocialLoginService $socialLoginService)
    {
        $this->socialLoginService = $socialLoginService;
    }

    public function redirectToProvider($provider)
    {
        $validProviders = ['google', 'facebook', 'apple'];

        if (! in_array($provider, $validProviders)) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            $user = $this->socialLoginService->findOrCreateUser($socialUser, $provider);

            // Check for email reuse fraud
            if ($this->socialLoginService->isEmailReused($socialUser->getEmail(), $provider)) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Email sudah digunakan dengan provider lain.',
                ]);
            }

            Auth::login($user, true);

            // Log social login
            activity('social_login', 'user', $user->id, [
                'provider' => $provider,
                'social_id' => $socialUser->getId(),
            ]);

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Login berhasil via '.ucfirst($provider));

        } catch (\Exception $e) {
            \Log::error('Social login error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'social' => 'Login via '.ucfirst($provider).' gagal. Silakan coba lagi.',
            ]);
        }
    }

    public function linkProvider(Request $request, $provider)
    {
        $request->validate([
            'provider_id' => 'required|string',
        ]);

        $user = Auth::user();

        try {
            $this->socialLoginService->linkProvider($user, $provider, $request->provider_id);

            activity('social_account_linked', 'user', $user->id, [
                'provider' => $provider,
            ]);

            return response()->json([
                'success' => true,
                'message' => ucfirst($provider).' account berhasil dihubungkan',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungkan akun '.ucfirst($provider),
            ], 422);
        }
    }

    public function unlinkProvider(Request $request, $provider)
    {
        $user = Auth::user();

        try {
            $this->socialLoginService->unlinkProvider($user, $provider);

            activity('social_account_unlinked', 'user', $user->id, [
                'provider' => $provider,
            ]);

            return response()->json([
                'success' => true,
                'message' => ucfirst($provider).' account berhasil diputus',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memutus akun '.ucfirst($provider),
            ], 422);
        }
    }
}
