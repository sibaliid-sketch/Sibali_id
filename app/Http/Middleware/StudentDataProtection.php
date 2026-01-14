<?php

namespace App\Http\Middleware;

use App\Services\Security\StudentSecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentDataProtection
{
    protected $securityService;

    public function __construct(StudentSecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if accessing student data
        $studentId = $request->route('student_id') ?? $request->input('student_id');

        if ($studentId) {
            // Verify access permissions
            $hasAccess = $this->securityService->verifyStudentAccess($user, $studentId, $request->route()->getName());

            if (! $hasAccess) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Access to student data denied',
                ], 403);
            }
        }

        $response = $next($request);

        // Apply field-level masking for sensitive data
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            $data = $this->applyDataMasking($data, $user);
            $response->setData($data);
        }

        return $response;
    }

    protected function applyDataMasking(array $data, $user): array
    {
        $sensitiveFields = [
            'national_id', 'birthdate', 'guardian_contact',
            'health_info', 'address', 'phone',
        ];

        // Check if user has full access scope
        if (! $this->hasFullAccessScope($user)) {
            foreach ($sensitiveFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = '[REDACTED]';
                }
            }
        }

        return $data;
    }

    protected function hasFullAccessScope($user): bool
    {
        return $user && in_array($user->user_type, ['admin', 'staff'])
            && $user->hasPermission('student_data_read');
    }
}
