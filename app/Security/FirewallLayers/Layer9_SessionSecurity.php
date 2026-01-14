<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class Layer9_SessionSecurity
{
    public function check(Request $request): array
    {
        // Skip for API routes
        if ($request->is('api/*')) {
            return [
                'allowed' => true,
                'layer' => 'Layer9_SessionSecurity',
                'reason' => 'api_route',
            ];
        }

        // Check session fingerprint
        if ($request->hasSession()) {
            $currentFingerprint = $this->generateFingerprint($request);
            $storedFingerprint = Session::get('_fingerprint');

            if ($storedFingerprint && $storedFingerprint !== $currentFingerprint) {
                \Log::warning('Session fingerprint mismatch', [
                    'ip' => $request->ip(),
                    'user_id' => auth()->id(),
                ]);

                Session::flush();

                return [
                    'allowed' => false,
                    'layer' => 'Layer9_SessionSecurity',
                    'reason' => 'fingerprint_mismatch',
                    'message' => 'Session security violation',
                    'status_code' => 401,
                ];
            }

            // Store fingerprint if not exists
            if (! $storedFingerprint) {
                Session::put('_fingerprint', $currentFingerprint);
            }
        }

        return [
            'allowed' => true,
            'layer' => 'Layer9_SessionSecurity',
        ];
    }

    protected function generateFingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip(),
            $request->userAgent() ?? '',
            $request->header('Accept-Language') ?? '',
        ]));
    }
}
