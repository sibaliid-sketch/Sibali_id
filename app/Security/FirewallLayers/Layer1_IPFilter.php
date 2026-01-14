<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;
use App\Services\Security\FirewallService;

class Layer1_IPFilter
{
    protected $firewallService;

    public function __construct(FirewallService $firewallService)
    {
        $this->firewallService = $firewallService;
    }

    public function check(Request $request): array
    {
        $ip = $request->ip();

        // Check whitelist first
        if ($this->firewallService->isWhitelisted($ip)) {
            return [
                'allowed' => true,
                'layer' => 'Layer1_IPFilter',
                'reason' => 'whitelisted'
            ];
        }

        // Check blacklist
        if ($this->firewallService->isBlacklisted($ip)) {
            return [
                'allowed' => false,
                'layer' => 'Layer1_IPFilter',
                'reason' => 'blacklisted',
                'message' => 'Your IP address has been blocked',
                'status_code' => 403
            ];
        }

        return [
            'allowed' => true,
            'layer' => 'Layer1_IPFilter'
        ];
    }
}
