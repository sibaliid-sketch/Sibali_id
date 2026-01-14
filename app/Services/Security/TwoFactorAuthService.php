<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorAuthService
{
    protected $qrWriter;

    public function __construct()
    {
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );
        $this->qrWriter = new Writer($renderer);
    }

    /**
     * Check if 2FA is enabled for user
     */
    public function isEnabled(User $user): bool
    {
        return !empty($user->two_factor_confirmed_at);
    }

    /**
     * Generate random 6-digit code
     */
    protected function generateCode(): string
    {
        return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Hash code with bcrypt
     */
    protected function hashCode(string $code): string
    {
        return bcrypt($code);
    }

    /**
     * Generate QR code SVG from code
     */
    protected function generateQR(string $code): string
    {
        return $this->qrWriter->writeString($code);
    }

    /**
     * Generate QR code for 2FA setup
     */
    public function generateQRCode(User $user): array
    {
        $code = $this->generateCode();
        $hash = $this->hashCode($code);
        $qr = $this->generateQR($code);

        // Store temp hash in session
        session(['2fa_setup_hash' => $hash, '2fa_setup_expires' => now()->addMinutes(5)]);

        return [
            'qr' => $qr,
            'code' => $code, // For debugging, remove in production
        ];
    }

    /**
     * Enable 2FA for user
     */
    public function enable(User $user, string $code): bool
    {
        $hash = session('2fa_setup_hash');
        $expires = session('2fa_setup_expires');

        if (!$hash || !$expires || now()->isAfter($expires)) {
            return false;
        }

        if (!password_verify($code, $hash)) {
            return false;
        }

        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = $this->generateRecoveryCodes();
        $user->save();

        // Clear session
        session()->forget(['2fa_setup_hash', '2fa_setup_expires']);

        return true;
    }

    /**
     * Verify 2FA code for login
     */
    public function verify(User $user, string $code): bool
    {
        $hash = session('2fa_login_hash');
        $expires = session('2fa_login_expires');

        if (!$hash || !$expires || now()->isAfter($expires)) {
            return false;
        }

        return password_verify($code, $hash);
    }

    /**
     * Send 2FA code for login (generate and store)
     */
    public function sendLoginCode(): array
    {
        $code = $this->generateCode();
        $hash = $this->hashCode($code);
        $qr = $this->generateQR($code);

        session(['2fa_login_hash' => $hash, '2fa_login_expires' => now()->addMinutes(5), '2fa_qr' => $qr]);

        return [
            'qr' => $qr,
            'code' => $code, // For debugging
        ];
    }

    /**
     * Get QR for login
     */
    public function getLoginQR(): ?string
    {
        return session('2fa_qr');
    }

    /**
     * Disable 2FA for user
     */
    public function disable(User $user): void
    {
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(User $user): Collection
    {
        $codes = $this->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $codes;
        $user->save();

        return collect($codes);
    }

    /**
     * Get recovery codes
     */
    public function getRecoveryCodes(User $user): Collection
    {
        return collect($user->two_factor_recovery_codes ?? []);
    }

    /**
     * Generate recovery codes
     */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(10) . '-' . Str::random(10);
        }
        return $codes;
    }
}
