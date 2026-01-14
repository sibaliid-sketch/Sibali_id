<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Layer17_ContentSecurityPolicy
{
    protected $cspDirectives = [
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'unsafe-inline'"],
        'style-src' => ["'self'", "'unsafe-inline'"],
        'img-src' => ["'self'", 'data:', 'https:'],
        'font-src' => ["'self'", 'data:'],
        'connect-src' => ["'self'"],
        'frame-ancestors' => ["'none'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
    ];

    public function check(Request $request): array
    {
        return [
            'allowed' => true,
            'layer' => 'Layer17_ContentSecurityPolicy',
            'csp' => $this->buildCSP(),
        ];
    }

    public function addCSPHeader(Response $response): Response
    {
        $csp = $this->buildCSP();

        $response->headers->set('Content-Security-Policy', $csp);

        // Also add report-only for monitoring
        if (config('app.env') !== 'production') {
            $response->headers->set('Content-Security-Policy-Report-Only', $csp);
        }

        return $response;
    }

    protected function buildCSP(): string
    {
        $directives = [];

        foreach ($this->cspDirectives as $directive => $sources) {
            $directives[] = $directive.' '.implode(' ', $sources);
        }

        return implode('; ', $directives);
    }

    public function addNonce(string $nonce): void
    {
        $this->cspDirectives['script-src'][] = "'nonce-{$nonce}'";
        $this->cspDirectives['style-src'][] = "'nonce-{$nonce}'";
    }
}
