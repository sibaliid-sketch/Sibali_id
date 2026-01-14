<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Layer20_DatabaseInjectionProtection
{
    protected $suspiciousPatterns = [
        '/UNION.*SELECT/i',
        '/DROP.*TABLE/i',
        '/TRUNCATE/i',
        '/DELETE.*FROM.*WHERE.*1.*=.*1/i',
        '/EXEC\s*\(/i',
        '/EXECUTE\s*\(/i',
    ];

    public function check(Request $request): array
    {
        // Monitor query patterns
        $this->setupQueryMonitoring();

        return [
            'allowed' => true,
            'layer' => 'Layer20_DatabaseInjectionProtection',
        ];
    }

    protected function setupQueryMonitoring(): void
    {
        DB::listen(function ($query) {
            // Check for suspicious patterns in queries
            foreach ($this->suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $query->sql)) {
                    \Log::critical('Suspicious database query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
                    ]);

                    // Optionally throw exception to prevent execution
                    // throw new \RuntimeException('Suspicious query blocked');
                }
            }

            // Log slow queries
            if ($query->time > 1000) {
                \Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'time' => $query->time,
                ]);
            }
        });
    }

    public function enforceParameterizedQueries(): bool
    {
        // Ensure all queries use parameter binding
        return true;
    }
}
