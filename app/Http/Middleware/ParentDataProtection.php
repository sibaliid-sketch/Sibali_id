<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditService;
use App\Services\Privacy\ConsentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParentDataProtection
{
    protected $consentService;

    protected $auditService;

    public function __construct(ConsentService $consentService, AuditService $auditService)
    {
        $this->consentService = $consentService;
        $this->auditService = $auditService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->user_type !== 'parent') {
            return $next($request);
        }

        // Check consent for accessing child data
        $childId = $request->route('child_id') ?? $request->input('child_id');

        if ($childId) {
            $hasConsent = $this->consentService->checkParentConsent($user->id, $childId);

            if (! $hasConsent) {
                $this->auditService->logUnauthorizedAccess([
                    'actor_id' => $user->id,
                    'resource' => 'child_data',
                    'child_id' => $childId,
                    'reason' => 'no_consent',
                ]);

                return response()->json([
                    'error' => 'Unauthorized access to child data',
                    'message' => 'Parental consent required',
                ], 403);
            }

            // Log authorized access
            $this->auditService->logAccess([
                'actor_id' => $user->id,
                'resource_type' => 'child_data',
                'resource_id' => $childId,
                'action' => $request->method(),
                'consent_verified' => true,
            ]);
        }

        $response = $next($request);

        // Mask sensitive PII in response if needed
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            $data = $this->maskSensitiveData($data);
            $response->setData($data);
        }

        return $response;
    }

    protected function maskSensitiveData(array $data): array
    {
        $sensitiveFields = ['national_id', 'phone', 'address', 'email'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->maskField($data[$field]);
            }
        }

        return $data;
    }

    protected function maskField(string $value): string
    {
        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2).str_repeat('*', $length - 4).substr($value, -2);
    }
}
