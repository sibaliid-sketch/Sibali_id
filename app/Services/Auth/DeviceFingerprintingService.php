<?php

namespace App\Services\Auth;

use App\Models\User\UserDevice;
use Illuminate\Http\Request;

class DeviceFingerprintingService
{
    public function fingerprint(Request $request): array
    {
        return [
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
            'screen_resolution' => $request->header('X-Screen-Resolution'), // Custom header
            'timezone' => $request->header('X-Timezone'),
            'platform' => $this->detectPlatform($request->userAgent()),
            'browser' => $this->detectBrowser($request->userAgent()),
            'device_type' => $this->detectDeviceType($request->userAgent()),
            'fingerprint_hash' => $this->generateFingerprintHash($request),
        ];
    }

    public function registerDevice(int $userId, array $fingerprint): void
    {
        $hash = $fingerprint['fingerprint_hash'];

        UserDevice::updateOrCreate(
            [
                'user_id' => $userId,
                'device_hash' => $hash,
            ],
            [
                'ip_address' => $fingerprint['ip'],
                'user_agent' => $fingerprint['user_agent'],
                'platform' => $fingerprint['platform'],
                'browser' => $fingerprint['browser'],
                'device_type' => $fingerprint['device_type'],
                'last_seen_at' => now(),
                'is_trusted' => true, // Mark as trusted after successful login
            ]
        );
    }

    public function isTrustedDevice(int $userId, array $fingerprint): bool
    {
        $hash = $fingerprint['fingerprint_hash'];

        $device = UserDevice::where('user_id', $userId)
            ->where('device_hash', $hash)
            ->where('is_trusted', true)
            ->first();

        return $device !== null;
    }

    public function getUserDevices(int $userId)
    {
        return UserDevice::where('user_id', $userId)
            ->orderBy('last_seen_at', 'desc')
            ->get();
    }

    public function revokeDevice(int $userId, string $deviceHash): bool
    {
        return UserDevice::where('user_id', $userId)
            ->where('device_hash', $deviceHash)
            ->update(['is_trusted' => false]);
    }

    protected function detectPlatform(string $userAgent): string
    {
        if (stripos($userAgent, 'windows') !== false) {
            return 'Windows';
        }
        if (stripos($userAgent, 'macintosh') !== false || stripos($userAgent, 'mac os x') !== false) {
            return 'macOS';
        }
        if (stripos($userAgent, 'linux') !== false) {
            return 'Linux';
        }
        if (stripos($userAgent, 'android') !== false) {
            return 'Android';
        }
        if (stripos($userAgent, 'iphone') !== false || stripos($userAgent, 'ipad') !== false) {
            return 'iOS';
        }

        return 'Unknown';
    }

    protected function detectBrowser(string $userAgent): string
    {
        if (stripos($userAgent, 'chrome') !== false && stripos($userAgent, 'edg') === false) {
            return 'Chrome';
        }
        if (stripos($userAgent, 'firefox') !== false) {
            return 'Firefox';
        }
        if (stripos($userAgent, 'safari') !== false && stripos($userAgent, 'chrome') === false) {
            return 'Safari';
        }
        if (stripos($userAgent, 'edg') !== false) {
            return 'Edge';
        }
        if (stripos($userAgent, 'opera') !== false) {
            return 'Opera';
        }

        return 'Unknown';
    }

    protected function detectDeviceType(string $userAgent): string
    {
        if (stripos($userAgent, 'mobile') !== false || stripos($userAgent, 'android') !== false || stripos($userAgent, 'iphone') !== false) {
            return 'Mobile';
        }
        if (stripos($userAgent, 'tablet') !== false || stripos($userAgent, 'ipad') !== false) {
            return 'Tablet';
        }

        return 'Desktop';
    }

    protected function generateFingerprintHash(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->ip(),
            $request->header('Accept-Language'),
            $this->detectPlatform($request->userAgent()),
            $this->detectBrowser($request->userAgent()),
            $this->detectDeviceType($request->userAgent()),
        ];

        return hash('sha256', implode('|', $components));
    }
}
