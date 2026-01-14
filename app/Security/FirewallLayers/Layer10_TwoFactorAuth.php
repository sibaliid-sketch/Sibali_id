<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer10_TwoFactorAuth
{
    protected $protectedRoutes = [
        'admin/*',
        'api/admin/*',
        'internal/*',
    ];

    public function check(Request $request): array
    {
        // Check if 2FA is required for this route
        if (! $this->requires2FA($request)) {
            return [
                'allowed' => true,
                'layer' => 'Layer10_TwoFactorAuth',
                'reason' => 'not_required',
            ];
        }

        // Check if user is authenticated
        if (! auth()->check()) {
            return [
                'allowed' => true,
                'layer' => 'Layer10_TwoFactorAuth',
                'reason' => 'not_authenticated',
            ];
        }

        $user = auth()->user();

        // Check if 2FA is enabled for user
        if (! $this->has2FAEnabled($user)) {
            return [
                'allowed' => true,
                'layer' => 'Layer10_TwoFactorAuth',
                'reason' => '2fa_not_enabled',
            ];
        }

        // Check if 2FA is verified in session
        if (! session('2fa_verified')) {
            return [
                'allowed' => false,
                'layer' => 'Layer10_TwoFactorAuth',
                'reason' => '2fa_required',
                'message' => 'Two-factor authentication required',
                'status_code' => 403,
            ];
        }

        return [
            'allowed' => true,
            'layer' => 'Layer10_TwoFactorAuth',
        ];
    }

    protected function requires2FA(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->protectedRoutes as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function has2FAEnabled($user): bool
    {
        return isset($user->two_factor_secret) && ! empty($user->two_factor_secret);
    }
}
