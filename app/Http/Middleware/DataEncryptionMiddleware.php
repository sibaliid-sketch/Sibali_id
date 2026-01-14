<?php

namespace App\Http\Middleware;

use App\Services\Security\DataEncryptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DataEncryptionMiddleware
{
    protected $encryptionService;

    protected $encryptedFields = [
        'email',
        'phone',
        'national_id',
        'card_last4',
        'bank_account',
        'proof_url',
        'payment_proof',
    ];

    public function __construct(DataEncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Decrypt incoming encrypted fields if present
        if ($request->isMethod('post') || $request->isMethod('put')) {
            $this->decryptRequestData($request);
        }

        $response = $next($request);

        // Encrypt outgoing sensitive fields in response
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $this->encryptResponseData($response);
        }

        return $response;
    }

    protected function decryptRequestData(Request $request): void
    {
        $data = $request->all();

        foreach ($this->encryptedFields as $field) {
            if (isset($data[$field]) && $this->isEncrypted($data[$field])) {
                try {
                    $context = $this->buildContext($request);
                    $data[$field] = $this->encryptionService->decrypt($data[$field], $context);
                } catch (\Exception $e) {
                    \Log::warning("Failed to decrypt field: {$field}", [
                        'error' => $e->getMessage(),
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        }

        $request->merge($data);
    }

    protected function encryptResponseData(\Illuminate\Http\JsonResponse $response): void
    {
        $data = $response->getData(true);

        if (is_array($data)) {
            $data = $this->encryptArrayFields($data);
            $response->setData($data);
        }
    }

    protected function encryptArrayFields(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->encryptArrayFields($value);
            } elseif (in_array($key, $this->encryptedFields) && ! $this->isEncrypted($value)) {
                try {
                    $context = $this->buildContext(request());
                    $data[$key] = $this->encryptionService->encrypt($value, $context);
                } catch (\Exception $e) {
                    \Log::warning("Failed to encrypt field: {$key}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $data;
    }

    protected function buildContext(Request $request): array
    {
        return [
            'tenant_id' => config('app.tenant_id', 'default'),
            'environment' => config('app.env'),
            'purpose' => 'data_protection',
            'user_id' => auth()->id(),
        ];
    }

    protected function isEncrypted(string $value): bool
    {
        // Check if value is already encrypted (basic check)
        return strpos($value, 'enc:') === 0;
    }
}
