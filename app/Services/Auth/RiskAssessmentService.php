<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RiskAssessmentService
{
    public function assessLoginRisk(Request $request, array $deviceFingerprint): float
    {
        $riskScore = 0.0;

        // Check IP reputation (simplified - in real implementation, use external service)
        $ipRisk = $this->assessIpRisk($request->ip());
        $riskScore += $ipRisk * 0.3;

        // Check device trust
        $deviceRisk = $this->assessDeviceRisk($deviceFingerprint);
        $riskScore += $deviceRisk * 0.4;

        // Check login patterns
        $patternRisk = $this->assessLoginPatternRisk($request);
        $riskScore += $patternRisk * 0.3;

        // Cap at 1.0
        return min(1.0, $riskScore);
    }

    public function assessIpRisk(string $ip): float
    {
        // Simplified IP risk assessment
        // In production, integrate with services like MaxMind, AbuseIPDB, etc.

        // Check if IP is from known VPN/proxy ranges
        if ($this->isFromVpnRange($ip)) {
            return 0.8;
        }

        // Check recent failed login attempts from this IP
        $failedAttempts = Cache::get("login_failures:{$ip}", 0);
        if ($failedAttempts > 10) {
            return 0.9;
        } elseif ($failedAttempts > 5) {
            return 0.6;
        } elseif ($failedAttempts > 2) {
            return 0.3;
        }

        // Check if IP is from unusual location for the user
        // This would require user location history
        // For now, return low risk
        return 0.1;
    }

    public function assessDeviceRisk(array $deviceFingerprint): float
    {
        $risk = 0.0;

        // Check if device is known
        if ($deviceFingerprint['device_type'] === 'Unknown') {
            $risk += 0.2;
        }

        // Check browser fingerprint consistency
        if ($this->isSuspiciousBrowser($deviceFingerprint['browser'])) {
            $risk += 0.3;
        }

        // Check for automation indicators
        if ($this->hasAutomationIndicators($deviceFingerprint)) {
            $risk += 0.5;
        }

        return min(1.0, $risk);
    }

    public function assessLoginPatternRisk(Request $request): float
    {
        $risk = 0.0;

        // Check login time patterns
        $hour = now()->hour;
        if ($hour < 6 || $hour > 22) { // Unusual hours
            $risk += 0.2;
        }

        // Check rapid login attempts
        $recentAttempts = Cache::get("recent_logins:{$request->ip()}", 0);
        if ($recentAttempts > 5) {
            $risk += 0.4;
        }

        // Check if login from multiple countries recently
        // This would require geo-tracking
        // For now, return based on attempt frequency
        return min(1.0, $risk);
    }

    protected function isFromVpnRange(string $ip): bool
    {
        // Simplified check - in production use proper VPN detection
        // Common VPN ranges (example)
        $vpnRanges = [
            '10.0.0.0/8', // Private range often used by VPNs
            // Add more known VPN ranges
        ];

        // Simple check - not accurate for production
        return str_starts_with($ip, '10.');
    }

    protected function isSuspiciousBrowser(string $browser): bool
    {
        $suspiciousBrowsers = ['Unknown', 'Bot', 'Crawler'];

        return in_array($browser, $suspiciousBrowsers);
    }

    protected function hasAutomationIndicators(array $fingerprint): bool
    {
        // Check for headless browser indicators
        if (stripos($fingerprint['user_agent'], 'headless') !== false) {
            return true;
        }

        // Check for missing common headers
        if (empty($fingerprint['accept_language'])) {
            return true;
        }

        return false;
    }

    public function recordFailedLogin(Request $request): void
    {
        $ip = $request->ip();
        $current = Cache::get("login_failures:{$ip}", 0);
        Cache::put("login_failures:{$ip}", $current + 1, now()->addMinutes(30));

        $recent = Cache::get("recent_logins:{$ip}", 0);
        Cache::put("recent_logins:{$ip}", $recent + 1, now()->addMinutes(5));
    }

    public function recordSuccessfulLogin(Request $request): void
    {
        $ip = $request->ip();
        Cache::forget("login_failures:{$ip}");
    }
}
