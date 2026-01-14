<?php

namespace App\Services\Privacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsentService
{
    public function checkParentConsent($parentId, $childId)
    {
        // Check if parent-child relationship exists
        $relationship = DB::table('students')
            ->where('id', $childId)
            ->where('parent_id', $parentId)
            ->first();

        if (!$relationship) {
            return false;
        }

        // Check if consent is granted (default true for direct parent)
        $consent = DB::table('parent_consents')
            ->where('parent_id', $parentId)
            ->where('child_id', $childId)
            ->where('consent_type', 'data_access')
            ->where('status', 'granted')
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();

        // If no explicit consent record, grant access for direct parent
        return $consent !== null || $relationship !== null;
    }

    public function grantConsent($parentId, $childId, $consentType = 'data_access', $expiresAt = null)
    {
        try {
            DB::table('parent_consents')->updateOrInsert(
                [
                    'parent_id' => $parentId,
                    'child_id' => $childId,
                    'consent_type' => $consentType,
                ],
                [
                    'status' => 'granted',
                    'granted_at' => now(),
                    'expires_at' => $expiresAt,
                    'updated_at' => now(),
                ]
            );

            Log::info('Consent granted', [
                'parent_id' => $parentId,
                'child_id' => $childId,
                'type' => $consentType
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to grant consent', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function revokeConsent($parentId, $childId, $consentType = 'data_access')
    {
        try {
            DB::table('parent_consents')
                ->where('parent_id', $parentId)
                ->where('child_id', $childId)
                ->where('consent_type', $consentType)
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);

            Log::info('Consent revoked', [
                'parent_id' => $parentId,
                'child_id' => $childId,
                'type' => $consentType
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to revoke consent', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
