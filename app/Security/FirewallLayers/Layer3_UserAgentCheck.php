<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer3_UserAgentCheck
{
    protected $blockedPatterns = [
        '/sqlmap/i',
        '/nikto/i',
        '/nmap/i',
        '/masscan/i',
        '/metasploit/i',
        '/burp/i',
        '/acunetix/i',
    ];

    public function check(Request $request): array
    {
        $userAgent = $request->userAgent();

        // Block empty user agents
        if (empty($userAgent)) {
            return [
                'allowed' => false,
                'layer' => 'Layer3_UserAgentCheck',
                'reason' => 'empty_user_agent',
                'message' => 'User agent required',
                'status_code' => 400,
            ];
        }

        // Check against blocked patterns
        foreach ($this->blockedPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                \Log::warning('Blocked user agent detected', [
                    'ip' => $request->ip(),
                    'user_agent' => $userAgent,
                ]);

                return [
                    'allowed' => false,
                    'layer' => 'Layer3_UserAgentCheck',
                    'reason' => 'blocked_user_agent',
                    'message' => 'Access denied',
                    'status_code' => 403,
                ];
            }
        }

        return [
            'allowed' => true,
            'layer' => 'Layer3_UserAgentCheck',
        ];
    }
}
