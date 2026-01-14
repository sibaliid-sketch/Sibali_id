<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentSecurityService
{
    public function verifyStudentAccess($user, $studentId, $routeName)
    {
        if (!$user) {
            return false;
        }

        // Admin and staff have full access
        if (in_array($user->user_type, ['admin', 'staff'])) {
            return $this->checkStaffPermission($user, 'student_data_read');
        }

        // Teachers can access their students
        if ($user->user_type === 'teacher') {
            return $this->checkTeacherStudentRelation($user->id, $studentId);
        }

        // Students can access their own data
        if ($user->user_type === 'student' && $user->id == $studentId) {
            return true;
        }

        // Parents can access their children's data
        if ($user->user_type === 'parent') {
            return $this->checkParentChildRelation($user->id, $studentId);
        }

        return false;
    }

    protected function checkStaffPermission($user, $permission)
    {
        // Check if user has the required permission
        // This is a simplified version - implement full RBAC as needed
        return true; // Placeholder
    }

    protected function checkTeacherStudentRelation($teacherId, $studentId)
    {
        return DB::table('class_enrollments')
            ->join('classes', 'class_enrollments.class_id', '=', 'classes.id')
            ->where('classes.teacher_id', $teacherId)
            ->where('class_enrollments.student_id', $studentId)
            ->exists();
    }

    protected function checkParentChildRelation($parentId, $studentId)
    {
        return DB::table('students')
            ->where('id', $studentId)
            ->where('parent_id', $parentId)
            ->exists();
    }

    public function maskSensitiveData(array $data, $user)
    {
        $sensitiveFields = [
            'national_id',
            'birthdate',
            'guardian_contact',
            'health_info',
            'address',
            'phone'
        ];

        // Check if user has full access
        if (!$this->hasFullDataAccess($user)) {
            foreach ($sensitiveFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = $this->maskField($data[$field]);
                }
            }
        }

        return $data;
    }

    protected function hasFullDataAccess($user)
    {
        return $user && in_array($user->user_type, ['admin', 'staff']);
    }

    protected function maskField($value)
    {
        if (empty($value)) {
            return $value;
        }

        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2) . str_repeat('*', $length - 4) . substr($value, -2);
    }
}
