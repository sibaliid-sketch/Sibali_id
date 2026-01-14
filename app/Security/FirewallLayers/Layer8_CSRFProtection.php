<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer8_CSRFProtection
{
    protected $excludedRoutes = [
        'api/*',
        'webhooks/*'
    ];

    public function check(Request $request): array
    {
        // Skip CSRF for excluded routes
        if ($this->shouldExclude($request)) {
            return [
                'allowed' => true,
                'layer' => 'Layer8_CSRFProtection',
                'reason' => 'excluded_route'
            ];
        }

        // Only check state-changing methods
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return [
                'allowed' => true,
                'layer' => 'Layer8_CSRFProtection'
            ];
        }

        // Check CSRF token
        $token = $request->header('X-CSRF-TOKEN') ?? $request->input('_token');

        if (empty($token)) {
            return [
                'allowed' => false,
                'layer' => 'Layer8_CSRFProtection',
                'reason' => 'missing_csrf_token',
                'message' => 'CSRF token missing',
                'status_code' => 419
            ];
        }

        // Verify token (Laravel's session will handle actual verification)
        // This is a pre-check layer
        if (!$this->isValidTokenFormat($token)) {
            return [
                'allowed' => false,
                'layer' => 'Layer8_CSRFProtection',
                'reason' => 'invalid_csrf_token',
                'message' => 'Invalid CSRF token format',
                'status_code' => 419
            ];
        }

        return [
            'allowed' => true,
            'layer' => 'Layer8_CSRFProtection'
        ];
    }

    protected function shouldExclude(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->excludedRoutes as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function isValidTokenFormat(string $token): bool
    {
        // Basic format check (40 chars alphanumeric)
        return preg_match('/^[a-zA-Z0-9]{40,}$/', $token);
    }
}
