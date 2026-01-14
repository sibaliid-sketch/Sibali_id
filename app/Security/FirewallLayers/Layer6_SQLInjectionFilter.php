<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer6_SQLInjectionFilter
{
    protected $sqlPatterns = [
        '/(\bUNION\b.*\bSELECT\b)/i',
        '/(\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i',
        '/(\bINSERT\b.*\bINTO\b.*\bVALUES\b)/i',
        '/(\bUPDATE\b.*\bSET\b)/i',
        '/(\bDELETE\b.*\bFROM\b)/i',
        '/(\bDROP\b.*\bTABLE\b)/i',
        '/(\bEXEC\b|\bEXECUTE\b)/i',
        '/(;|\-\-|\/\*|\*\/|xp_|sp_)/i',
        '/(\bOR\b.*=.*)/i',
        '/(\bAND\b.*=.*)/i',
        '/(\'|\"|`)(.*)(\'|\"|`).*=.*(\'|\"|`)/i',
    ];

    public function check(Request $request): array
    {
        $inputs = $this->getAllInputs($request);

        foreach ($inputs as $key => $value) {
            if (is_string($value) && $this->containsSQLInjection($value)) {
                \Log::warning('SQL Injection attempt detected', [
                    'ip' => $request->ip(),
                    'field' => $key,
                    'value' => substr($value, 0, 100),
                    'user_agent' => $request->userAgent(),
                ]);

                return [
                    'allowed' => false,
                    'layer' => 'Layer6_SQLInjectionFilter',
                    'reason' => 'sql_injection_detected',
                    'message' => 'Invalid input detected',
                    'status_code' => 400,
                ];
            }
        }

        return [
            'allowed' => true,
            'layer' => 'Layer6_SQLInjectionFilter',
        ];
    }

    protected function getAllInputs(Request $request): array
    {
        return array_merge(
            $request->all(),
            $request->query->all(),
            $request->route() ? $request->route()->parameters() : []
        );
    }

    protected function containsSQLInjection(string $value): bool
    {
        foreach ($this->sqlPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
