<?php

namespace App\Services\Security;

use Illuminate\Contracts\Encryption\DecryptException;

class DataEncryptionService
{
    protected $method = 'AES-256-GCM';

    protected $keyRotationDays;

    public function __construct()
    {
        $this->keyRotationDays = config('security.key_rotation_days', 90);
    }

    /**
     * Encrypt data with context-aware key
     */
    public function encrypt($data, array $context = []): string
    {
        if (empty($data)) {
            return $data;
        }

        try {
            $key = $this->getEncryptionKey($context);
            $iv = random_bytes(16);
            $tag = '';

            $encrypted = openssl_encrypt(
                $data,
                $this->method,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                $this->buildAAD($context)
            );

            if ($encrypted === false) {
                throw new \RuntimeException('Encryption failed');
            }

            // Combine IV + tag + encrypted data
            $result = base64_encode($iv.$tag.$encrypted);

            return 'enc:'.$result;
        } catch (\Exception $e) {
            \Log::error('Encryption failed', [
                'error' => $e->getMessage(),
                'context' => $context,
            ]);
            throw $e;
        }
    }

    /**
     * Decrypt data with context-aware key
     */
    public function decrypt(string $data, array $context = []): string
    {
        if (empty($data) || strpos($data, 'enc:') !== 0) {
            return $data;
        }

        try {
            $data = substr($data, 4); // Remove 'enc:' prefix
            $decoded = base64_decode($data);

            $iv = substr($decoded, 0, 16);
            $tag = substr($decoded, 16, 16);
            $encrypted = substr($decoded, 32);

            $key = $this->getEncryptionKey($context);

            $decrypted = openssl_decrypt(
                $encrypted,
                $this->method,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                $this->buildAAD($context)
            );

            if ($decrypted === false) {
                throw new DecryptException('Decryption failed');
            }

            return $decrypted;
        } catch (\Exception $e) {
            \Log::error('Decryption failed', [
                'error' => $e->getMessage(),
                'context' => $context,
            ]);
            throw $e;
        }
    }

    /**
     * Get encryption key based on context
     */
    protected function getEncryptionKey(array $context): string
    {
        $baseKey = config('app.key');

        if (empty($baseKey)) {
            throw new \RuntimeException('Application key not set');
        }

        // Derive key from base key and context
        $contextString = json_encode($context);
        $derivedKey = hash_pbkdf2('sha256', $baseKey, $contextString, 10000, 32, true);

        return $derivedKey;
    }

    /**
     * Build Additional Authenticated Data (AAD) for GCM mode
     */
    protected function buildAAD(array $context): string
    {
        return json_encode([
            'tenant_id' => $context['tenant_id'] ?? 'default',
            'environment' => $context['environment'] ?? config('app.env'),
            'purpose' => $context['purpose'] ?? 'general',
        ]);
    }

    /**
     * Check if key rotation is needed
     */
    public function needsKeyRotation(): bool
    {
        $lastRotation = cache()->get('encryption_key_last_rotation');

        if (! $lastRotation) {
            return true;
        }

        $daysSinceRotation = now()->diffInDays($lastRotation);

        return $daysSinceRotation >= $this->keyRotationDays;
    }

    /**
     * Rotate encryption keys
     */
    public function rotateKeys(): void
    {
        // This should be implemented with proper key management system (KMS)
        \Log::info('Key rotation initiated');

        cache()->put('encryption_key_last_rotation', now(), now()->addDays($this->keyRotationDays));

        // TODO: Implement actual key rotation with KMS
        // 1. Generate new key
        // 2. Re-encrypt data with new key
        // 3. Update key reference
        // 4. Maintain old key for grace period
    }

    /**
     * Hash sensitive data for comparison without storing plaintext
     */
    public function hash(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Verify hashed data
     */
    public function verifyHash(string $data, string $hash): bool
    {
        return hash_equals($hash, $this->hash($data));
    }
}
