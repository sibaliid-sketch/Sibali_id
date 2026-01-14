<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer15_CORSProtection
{
    protected $allowedOrigins = [];

    public function __construct()
    {
        $this->allowedOrigins = config('cors.allowed_origins', [
            config('app.url'),
        ]);
    }

    public function check(Request $request): array
    {
        $origin = $request->header('Origin');

        // No origin header means same-origin request
        if (! $origin) {
            return [
                'allowed' => true,
                'layer' => 'Layer15_CORSProtection',
                'reason' => 'same_origin',
            ];
        }

        // Check if origin is allowed
        if (! $this->isAllowedOrigin($origin)) {
            \Log::warning('CORS violation', [
                'origin' => $origin,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return [
                'allowed' => false,
                'layer' => 'Layer15_CORSProtection',
                'reason' => 'origin_not_allowed',
                'message' => 'Origin not allowed',
                'status_code' => 403,
            ];
        }

        return [
            'allowed' => true,
            'layer' => 'Layer15_CORSProtection',
            'origin' => $origin,
        ];
    }

    protected function isAllowedOrigin(string $origin): bool
    {
        // Check exact match
        if (in_array($origin, $this->allowedOrigins)) {
            return true;
        }

        // Check wildcard patterns
        foreach ($this->allowedOrigins as $allowed) {
            if ($allowed === '*') {
                return true;
            }

            if (fnmatch($allowed, $origin)) {
                return true;
            }
        }

        return false;
    }
}
