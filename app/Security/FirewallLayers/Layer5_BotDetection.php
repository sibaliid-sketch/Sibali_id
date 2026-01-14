<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Layer5_BotDetection
{
    protected $botPatterns = [
        '/bot/i',
        '/crawler/i',
        '/spider/i',
        '/scraper/i',
        '/curl/i',
        '/wget/i',
        '/python/i',
        '/java/i',
    ];

    protected $allowedBots = [
        'Googlebot',
        'Bingbot',
        'Slackbot',
        'facebookexternalhit',
    ];

    public function check(Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';
        $ip = $request->ip();

        // Check if it's an allowed bot
        if ($this->isAllowedBot($userAgent)) {
            return [
                'allowed' => true,
                'layer' => 'Layer5_BotDetection',
                'reason' => 'allowed_bot',
            ];
        }

        // Check user agent patterns
        if ($this->isSuspiciousBot($userAgent)) {
            $this->recordBotActivity($ip, $userAgent);

            return [
                'allowed' => false,
                'layer' => 'Layer5_BotDetection',
                'reason' => 'suspicious_bot',
                'message' => 'Bot activity detected',
                'status_code' => 403,
            ];
        }

        // Behavioral analysis
        $behaviorScore = $this->analyzeBehavior($request);

        if ($behaviorScore > config('firewall.bot_detection.threshold', 0.7)) {
            return [
                'allowed' => false,
                'layer' => 'Layer5_BotDetection',
                'reason' => 'bot_behavior',
                'message' => 'Automated behavior detected',
                'status_code' => 403,
            ];
        }

        return [
            'allowed' => true,
            'layer' => 'Layer5_BotDetection',
        ];
    }

    protected function isAllowedBot(string $userAgent): bool
    {
        foreach ($this->allowedBots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function isSuspiciousBot(string $userAgent): bool
    {
        if (empty($userAgent)) {
            return true;
        }

        foreach ($this->botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    protected function analyzeBehavior(Request $request): float
    {
        $ip = $request->ip();
        $score = 0.0;

        // Check request frequency
        $requestCount = Cache::get("bot_detect:requests:{$ip}", 0);
        Cache::put("bot_detect:requests:{$ip}", $requestCount + 1, now()->addMinutes(5));

        if ($requestCount > 100) {
            $score += 0.3;
        }

        // Check for missing common headers
        if (! $request->header('Accept-Language')) {
            $score += 0.2;
        }

        if (! $request->header('Accept-Encoding')) {
            $score += 0.2;
        }

        // Check for suspicious patterns in request
        if ($this->hasSuspiciousPatterns($request)) {
            $score += 0.3;
        }

        return min($score, 1.0);
    }

    protected function hasSuspiciousPatterns(Request $request): bool
    {
        // Check for common scraping patterns
        $path = $request->path();

        $suspiciousPatterns = [
            '/\.php$/i',
            '/admin/i',
            '/wp-/i',
            '/\.env/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function recordBotActivity(string $ip, string $userAgent): void
    {
        Cache::increment("bot_detect:blocked:{$ip}");

        \Log::info('Bot activity detected', [
            'ip' => $ip,
            'user_agent' => $userAgent,
            'count' => Cache::get("bot_detect:blocked:{$ip}", 1),
        ]);
    }
}
