<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FirewallService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('firewall');
    }

    /**
     * Log firewall request
     */
    public function logRequest(Request $request, string $action, array $details = []): void
    {
        try {
            DB::table('firewall_logs')->insert([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'path' => $request->path(),
                'action' => $action,
                'layer' => $details['layer'] ?? null,
                'reason' => $details['reason'] ?? null,
                'payload' => json_encode($details),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log firewall request', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if IP is whitelisted
     */
    public function isWhitelisted(string $ip): bool
    {
        $whitelist = $this->config['ip_whitelist'] ?? [];

        foreach ($whitelist as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is blacklisted
     */
    public function isBlacklisted(string $ip): bool
    {
        // Check permanent blacklist
        $blacklist = $this->config['ip_blacklist'] ?? [];

        foreach ($blacklist as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        // Check temporary blocks
        return Cache::has("firewall:blocked:{$ip}");
    }

    /**
     * Block IP temporarily
     */
    public function blockIP(string $ip, int $minutes = 60, string $reason = ''): void
    {
        Cache::put("firewall:blocked:{$ip}", [
            'reason' => $reason,
            'blocked_at' => now(),
        ], now()->addMinutes($minutes));

        $this->logRequest(new Request, 'ip_blocked', [
            'ip' => $ip,
            'reason' => $reason,
            'duration' => $minutes,
        ]);
    }

    /**
     * Unblock IP
     */
    public function unblockIP(string $ip): void
    {
        Cache::forget("firewall:blocked:{$ip}");
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range);

        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - (int) $mask);

        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }

    /**
     * Get rate limit for route
     */
    public function getRateLimit(string $route): array
    {
        $limits = $this->config['rate_limits'] ?? [];

        foreach ($limits as $pattern => $limit) {
            if (fnmatch($pattern, $route)) {
                return [
                    'max_attempts' => $limit['max_attempts'] ?? 60,
                    'decay_minutes' => $limit['decay_minutes'] ?? 1,
                    'burst' => $limit['burst'] ?? 10,
                ];
            }
        }

        return [
            'max_attempts' => 60,
            'decay_minutes' => 1,
            'burst' => 10,
        ];
    }

    /**
     * Check rate limit for key
     */
    public function checkRateLimit(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $attempts = Cache::get("rate_limit:{$key}", 0);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        Cache::put("rate_limit:{$key}", $attempts + 1, now()->addMinutes($decayMinutes));

        return true;
    }

    /**
     * Get firewall statistics
     */
    public function getStatistics(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        return [
            'total_requests' => DB::table('firewall_logs')
                ->where('created_at', '>=', $since)
                ->count(),
            'blocked_requests' => DB::table('firewall_logs')
                ->where('created_at', '>=', $since)
                ->where('action', 'blocked')
                ->count(),
            'by_layer' => DB::table('firewall_logs')
                ->where('created_at', '>=', $since)
                ->where('action', 'blocked')
                ->select('layer', DB::raw('count(*) as count'))
                ->groupBy('layer')
                ->get(),
            'top_blocked_ips' => DB::table('firewall_logs')
                ->where('created_at', '>=', $since)
                ->where('action', 'blocked')
                ->select('ip', DB::raw('count(*) as count'))
                ->groupBy('ip')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Update firewall rules dynamically
     */
    public function updateRules(array $rules): void
    {
        foreach ($rules as $key => $value) {
            config(["firewall.{$key}" => $value]);
        }

        // Cache updated rules
        Cache::put('firewall:rules', config('firewall'), now()->addHours(24));

        \Log::info('Firewall rules updated', ['rules' => $rules]);
    }

    /**
     * Hot reload firewall configuration
     */
    public function reload(): void
    {
        Cache::forget('firewall:rules');
        $this->config = config('firewall');

        \Log::info('Firewall configuration reloaded');
    }
}
