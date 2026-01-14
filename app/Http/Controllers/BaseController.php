<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as LaravelController;

class BaseController extends LaravelController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return success JSON response
     */
    protected function successResponse($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return error JSON response
     */
    protected function errorResponse($message = 'Error', $code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return validation error response
     */
    protected function validationErrorResponse($errors)
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Return not found response
     */
    protected function notFoundResponse($message = 'Resource not found')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorizedResponse($message = 'Unauthorized')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }

    /**
     * Return forbidden response
     */
    protected function forbiddenResponse($message = 'Forbidden')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Check if user has permission
     */
    protected function checkPermission($permission)
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->hasPermission($permission);
    }

    /**
     * Check if user has role
     */
    protected function checkRole($role)
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->hasRole($role);
    }

    /**
     * Get authenticated user
     */
    protected function getUser()
    {
        return auth()->user();
    }

    /**
     * Log activity
     */
    protected function logActivity($action, $targetType = null, $targetId = null, $meta = [])
    {
        if (! auth()->check()) {
            return;
        }

        DB::table('activity_logs')->insert([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'meta' => json_encode($meta),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
