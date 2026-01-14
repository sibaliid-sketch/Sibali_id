<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Layer18_HTTPHeadersSecurity
{
    protected $securityHeaders = [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ];

    public function check(Request $request): array
    {
        // This layer doesn't block requests, it adds headers to responses
        // The actual header addition happens in terminate() method or response middleware

        return [
            'allowed' => true,
            'layer' => 'Layer18_HTTPHeadersSecurity',
            'headers' => $this->securityHeaders,
        ];
    }

    public function addSecurityHeaders(Response $response): Response
    {
        foreach ($this->securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }

        // Add HSTS in production
        if (! app()->environment('local')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
