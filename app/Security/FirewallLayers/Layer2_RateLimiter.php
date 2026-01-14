<?php

namespace App\Security\FirewallLayers;

use App\Services\Security\FirewallService;
use Illuminate\Http\Request;

class Layer2_RateLimiter
{
    protected $firewallService;

    public function __construct(FirewallService $firewallService)
    {
        $this->firewallService = $firewallService;
    }

    public function check(Request $request): array
    {
        $route = $request->path();
        $limits = $this->firewallService->getRateLimit($route);

        // Create rate limit key based on IP and route
        $key = $this->getRateLimitKey($request);

        $allowed = $this->firewallService->checkRateLimit(
            $key,
            $limits['max_attempts'],
            $limits['decay_minutes']
        );

        if (! $allowed) {
            return [
                'allowed' => false,
                'layer' => 'Layer2_RateLimiter',
                'reason' => 'rate_limit_exceeded',
                'message' => 'Too many requests. Please try again later.',
                'status_code' => 429,
            ];
        }

        return [
            'allowed' => true,
            'layer' => 'Layer2_RateLimiter',
        ];
    }

    protected function getRateLimitKey(Request $request): string
    {
        $ip = $request->ip();
        $route = $request->path();
        $user = auth()->id() ?? 'guest';

        return "rate_limit:{$ip}:{$route}:{$user}";
    }
}
