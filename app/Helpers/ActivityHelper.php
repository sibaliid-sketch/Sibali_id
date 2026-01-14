<?php

if (!function_exists('activity')) {
    /**
     * Log user activity
     *
     * @param string $action
     * @param string|null $targetType
     * @param mixed|null $targetId
     * @param array $meta
     * @return void
     */
    function activity($action, $targetType = null, $targetId = null, $meta = [])
    {
        try {
            $userId = auth()->id();

            if (!$userId) {
                return;
            }

            \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
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
            \Illuminate\Support\Facades\Log::error('Failed to log activity', [
                'error' => $e->getMessage(),
                'action' => $action
            ]);
        }
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format number as Indonesian Rupiah
     *
     * @param float $amount
     * @return string
     */
    function format_currency($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

if (!function_exists('user_avatar')) {
    /**
     * Get user avatar URL or default
     *
     * @param mixed $user
     * @return string
     */
    function user_avatar($user)
    {
        if ($user && isset($user->avatar) && $user->avatar) {
            return asset('storage/' . $user->avatar);
        }

        return asset('images/default-avatar.png');
    }
}

if (!function_exists('user_type_label')) {
    /**
     * Get user type label in Indonesian
     *
     * @param string $userType
     * @return string
     */
    function user_type_label($userType)
    {
        $labels = [
            'student' => 'Siswa',
            'parent' => 'Orang Tua',
            'teacher' => 'Guru',
            'staff' => 'Staff',
            'admin' => 'Administrator',
        ];

        return $labels[$userType] ?? ucfirst($userType);
    }
}

if (!function_exists('payment_status_badge')) {
    /**
     * Get payment status badge HTML
     *
     * @param string $status
     * @return string
     */
    function payment_status_badge($status)
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'submitted' => '<span class="badge badge-info">Menunggu Verifikasi</span>',
            'verified' => '<span class="badge badge-success">Terverifikasi</span>',
            'rejected' => '<span class="badge badge-danger">Ditolak</span>',
        ];

        return $badges[$status] ?? '<span class="badge">' . ucfirst($status) . '</span>';
    }
}

if (!function_exists('class_status_badge')) {
    /**
     * Get class status badge HTML
     *
     * @param string $status
     * @return string
     */
    function class_status_badge($status)
    {
        $badges = [
            'active' => '<span class="badge badge-success">Aktif</span>',
            'inactive' => '<span class="badge badge-warning">Tidak Aktif</span>',
            'completed' => '<span class="badge badge-info">Selesai</span>',
            'cancelled' => '<span class="badge badge-danger">Dibatalkan</span>',
        ];

        return $badges[$status] ?? '<span class="badge">' . ucfirst($status) . '</span>';
    }
}

if (!function_exists('grade_color')) {
    /**
     * Get grade color class
     *
     * @param float $grade
     * @return string
     */
    function grade_color($grade)
    {
        if ($grade >= 90) return 'text-green-600';
        if ($grade >= 80) return 'text-blue-600';
        if ($grade >= 70) return 'text-yellow-600';
        if ($grade >= 60) return 'text-orange-600';
        return 'text-red-600';
    }
}

if (!function_exists('time_ago')) {
    /**
     * Get human readable time ago
     *
     * @param string|\DateTime $datetime
     * @return string
     */
    function time_ago($datetime)
    {
        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }

        return $datetime->diffForHumans();
    }
}
