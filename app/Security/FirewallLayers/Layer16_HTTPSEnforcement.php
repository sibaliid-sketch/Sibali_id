<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer16_HTTPSEnforcement
{
    public function check(Request $request): array
    {
        // Skip in local development
        if (app()->environment('local')) {
            return [
                'allowed' => true,
                'layer' => 'Layer16_HTTPSEnforcement',
                'reason' => 'local_env',
            ];
        }

        // Check if request is secure
        if (! $request->secure() && ! $this->isTrustedProxy($request)) {
            return [
                'allowed' => false,
                'layer' => 'Layer16_HTTPSEnforcement',
                'reason' => 'https_required',
                'message' => 'HTTPS required',
                'status_code' => 426,
                'redirect' => $request->fullUrlWithQuery([]),
            ];
        }

        return [
            'allowed' => true,
            'layer' => 'Layer16_HTTPSEnforcement',
        ];
    }

    protected function isTrustedProxy(Request $request): bool
    {
        // Check if behind trusted proxy with X-Forwarded-Proto
        return $request->header('X-Forwarded-Proto') === 'https';
    }
}
