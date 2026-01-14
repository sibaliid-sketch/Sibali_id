<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer7_XSSFilter
{
    protected $xssPatterns = [
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<embed\b[^>]*>/i',
        '/<object\b[^>]*>/i',
        '/eval\s*\(/i',
        '/expression\s*\(/i',
        '/vbscript:/i',
        '/data:text\/html/i',
    ];

    public function check(Request $request): array
    {
        $inputs = $this->getAllInputs($request);

        foreach ($inputs as $key => $value) {
            if (is_string($value) && $this->containsXSS($value)) {
                \Log::warning('XSS attempt detected', [
                    'ip' => $request->ip(),
                    'field' => $key,
                    'value' => substr($value, 0, 100),
                    'user_agent' => $request->userAgent(),
                ]);

                return [
                    'allowed' => false,
                    'layer' => 'Layer7_XSSFilter',
                    'reason' => 'xss_detected',
                    'message' => 'Invalid input detected',
                    'status_code' => 400,
                ];
            }
        }

        return [
            'allowed' => true,
            'layer' => 'Layer7_XSSFilter',
        ];
    }

    protected function getAllInputs(Request $request): array
    {
        return array_merge(
            $request->all(),
            $request->query->all()
        );
    }

    protected function containsXSS(string $value): bool
    {
        foreach ($this->xssPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    public function sanitize(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
