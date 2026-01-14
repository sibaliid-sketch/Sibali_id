<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer4_GeoBlocking
{
    public function check(Request $request): array
    {
        // Check if geo-blocking is enabled
        if (!config('firewall.geo_blocking.enabled', false)) {
            return [
                'allowed' => true,
                'layer' => 'Layer4_GeoBlocking',
                'reason' => 'disabled'
            ];
        }

        $ip = $request->ip();
        $country = $this->getCountryCode($ip);

        // Check allowed countries
        $allowedCountries = config('firewall.geo_blocking.allowed_countries', []);

        if (!empty($allowedCountries) && !in_array($country, $allowedCountries)) {
            \Log::info('Geo-blocking triggered', [
                'ip' => $ip,
                'country' => $country,
                'path' => $request->path()
            ]);

            return [
                'allowed' => false,
                'layer' => 'Layer4_GeoBlocking',
                'reason' => 'country_blocked',
                'message' => 'Access from your location is not permitted',
                'status_code' => 403
            ];
        }

        return [
            'allowed' => true,
            'layer' => 'Layer4_GeoBlocking'
        ];
    }

    protected function getCountryCode(string $ip): string
    {
        // Simple implementation - in production use GeoIP2 or similar
        // For now, return 'ID' for local IPs
        if ($this->isLocalIP($ip)) {
            return 'ID';
        }

        // TODO: Implement actual GeoIP lookup
        // Example: use MaxMind GeoIP2 database
        return 'UNKNOWN';
    }

    protected function isLocalIP(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']) ||
               strpos($ip, '192.168.') === 0 ||
               strpos($ip, '10.') === 0;
    }
}
