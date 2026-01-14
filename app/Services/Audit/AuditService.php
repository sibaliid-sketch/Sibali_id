<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditService
{
    public function logAccess(array $data)
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $data['actor_id'],
                'action' => 'access',
                'target_type' => $data['resource_type'],
                'target_id' => $data['resource_id'],
                'meta' => json_encode([
                    'method' => $data['action'],
                    'consent_verified' => $data['consent_verified'] ?? false,
                ]),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log access', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function logUnauthorizedAccess(array $data)
    {
        try {
            DB::table('security_audit_logs')->insert([
                'user_id' => $data['actor_id'],
                'event_type' => 'unauthorized_access',
                'resource' => $data['resource'],
                'resource_id' => $data['child_id'] ?? null,
                'reason' => $data['reason'],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'severity' => 'medium',
                'created_at' => now(),
            ]);

            Log::warning('Unauthorized access attempt', $data);
        } catch (\Exception $e) {
            Log::error('Failed to log unauthorized access', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function logActivity($userId, $action, $targetType = null, $targetId = null, $meta = [])
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'meta' => json_encode($meta),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log activity', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'action' => $action
            ]);
        }
    }

    public function getAuditLogs(array $filters = [])
    {
        $query = DB::table('activity_logs')
            ->join('users', 'activity_logs.user_id', '=', 'users.id')
            ->select('activity_logs.*', 'users.name as user_name');

        if (isset($filters['user_id'])) {
            $query->where('activity_logs.user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('activity_logs.action', $filters['action']);
        }

        if (isset($filters['date_from'])) {
            $query->where('activity_logs.created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('activity_logs.created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('activity_logs.created_at', 'desc')
                    ->paginate($filters['per_page'] ?? 50);
    }
}
