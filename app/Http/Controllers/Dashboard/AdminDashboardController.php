<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,staff']);
    }

    public function index()
    {
        $data = Cache::remember('admin_dashboard_data', 300, function () {
            return [
                'stats' => $this->getSystemStats(),
                'recent_registrations' => $this->getRecentRegistrations(),
                'pending_verifications' => $this->getPendingVerifications(),
                'system_health' => $this->getSystemHealth(),
                'recent_activities' => $this->getRecentActivities(),
                'revenue_summary' => $this->getRevenueSummary(),
            ];
        });

        return view('dashboard.admin.index', $data);
    }

    protected function getSystemStats()
    {
        return [
            'total_users' => DB::table('users')->count(),
            'total_students' => DB::table('students')->count(),
            'total_classes' => DB::table('classes')->count(),
            'active_classes' => DB::table('classes')->where('status', 'active')->count(),
            'total_teachers' => DB::table('users')->where('user_type', 'teacher')->count(),
            'pending_payments' => DB::table('payments')->where('status', 'pending')->count(),
            'unverified_payments' => DB::table('payments')->where('status', 'submitted')->whereNull('verified_at')->count(),
            'active_students' => DB::table('class_enrollments')
                ->distinct('student_id')
                ->count(),
        ];
    }

    protected function getRecentRegistrations()
    {
        return DB::table('users')
            ->select('id', 'name', 'email', 'user_type', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getPendingVerifications()
    {
        return DB::table('payments')
            ->join('users', 'payments.student_id', '=', 'users.id')
            ->where('payments.status', 'submitted')
            ->whereNull('payments.verified_at')
            ->select('payments.*', 'users.name as student_name')
            ->orderBy('payments.created_at', 'asc')
            ->limit(10)
            ->get();
    }

    protected function getSystemHealth()
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'queue' => $this->checkQueueHealth(),
        ];
    }

    protected function checkDatabaseHealth()
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'healthy', 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkCacheHealth()
    {
        try {
            Cache::put('health_check', true, 10);
            $result = Cache::get('health_check');

            return ['status' => $result ? 'healthy' : 'warning', 'message' => $result ? 'Working' : 'Not working'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkStorageHealth()
    {
        $storagePath = storage_path();
        $freeSpace = disk_free_space($storagePath);
        $totalSpace = disk_total_space($storagePath);
        $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 1);

        return [
            'status' => $usedPercent < 90 ? 'healthy' : 'warning',
            'message' => "Used: {$usedPercent}%",
            'free_space' => $this->formatBytes($freeSpace),
        ];
    }

    protected function checkQueueHealth()
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();

            return [
                'status' => $failedJobs < 10 ? 'healthy' : 'warning',
                'message' => "Failed jobs: {$failedJobs}",
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function getRecentActivities()
    {
        return DB::table('activity_logs')
            ->join('users', 'activity_logs.user_id', '=', 'users.id')
            ->select('activity_logs.*', 'users.name as user_name')
            ->orderBy('activity_logs.created_at', 'desc')
            ->limit(15)
            ->get();
    }

    protected function getRevenueSummary()
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return [
            'today' => DB::table('payments')
                ->where('status', 'verified')
                ->whereDate('verified_at', $today)
                ->sum('amount'),
            'this_month' => DB::table('payments')
                ->where('status', 'verified')
                ->whereDate('verified_at', '>=', $thisMonth)
                ->sum('amount'),
            'pending' => DB::table('payments')
                ->where('status', 'pending')
                ->sum('amount'),
        ];
    }

    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
