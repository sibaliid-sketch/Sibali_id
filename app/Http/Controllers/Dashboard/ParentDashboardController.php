<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ParentDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'user.type:parent']);
    }

    public function index()
    {
        $parentId = auth()->id();

        $data = [
            'parent' => $this->getParentProfile($parentId),
            'children' => $this->getChildren($parentId),
            'summary' => $this->getChildrenSummary($parentId),
            'recent_activities' => $this->getRecentActivities($parentId),
            'upcoming_payments' => $this->getUpcomingPayments($parentId),
            'notifications' => $this->getNotifications($parentId),
        ];

        return view('dashboard.parent.index', $data);
    }

    public function children()
    {
        $parentId = auth()->id();

        $children = $this->getChildren($parentId);

        return view('dashboard.parent.children', compact('children'));
    }

    public function childProgress($childId)
    {
        $parentId = auth()->id();

        // Verify parent-child relationship
        $child = DB::table('students')
            ->where('id', $childId)
            ->where('parent_id', $parentId)
            ->first();

        if (! $child) {
            abort(403, 'Unauthorized access to child data');
        }

        $data = [
            'child' => $child,
            'grades' => $this->getChildGrades($childId),
            'attendance' => $this->getChildAttendance($childId),
            'assignments' => $this->getChildAssignments($childId),
            'progress' => $this->getChildProgress($childId),
        ];

        return view('dashboard.parent.child-progress', $data);
    }

    protected function getParentProfile($parentId)
    {
        return DB::table('parents')
            ->join('users', 'parents.id', '=', 'users.id')
            ->where('parents.id', $parentId)
            ->select('users.*', 'parents.*')
            ->first();
    }

    protected function getChildren($parentId)
    {
        return DB::table('students')
            ->join('users', 'students.id', '=', 'users.id')
            ->where('students.parent_id', $parentId)
            ->select('users.id', 'users.name', 'users.email', 'students.*')
            ->get();
    }

    protected function getChildrenSummary($parentId)
    {
        $children = $this->getChildren($parentId);

        $totalChildren = count($children);
        $activeClasses = 0;
        $pendingPayments = 0;

        foreach ($children as $child) {
            $activeClasses += DB::table('class_enrollments')
                ->join('classes', 'class_enrollments.class_id', '=', 'classes.id')
                ->where('class_enrollments.student_id', $child->id)
                ->where('classes.status', 'active')
                ->count();

            $pendingPayments += DB::table('payments')
                ->where('student_id', $child->id)
                ->where('status', 'pending')
                ->count();
        }

        return [
            'total_children' => $totalChildren,
            'active_classes' => $activeClasses,
            'pending_payments' => $pendingPayments,
        ];
    }

    protected function getRecentActivities($parentId)
    {
        $childIds = $this->getChildren($parentId)->pluck('id');

        return DB::table('activity_logs')
            ->whereIn('user_id', $childIds)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getUpcomingPayments($parentId)
    {
        $childIds = $this->getChildren($parentId)->pluck('id');

        return DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->whereIn('payments.student_id', $childIds)
            ->where('payments.status', 'pending')
            ->select('payments.*', 'invoices.invoice_number', 'invoices.total')
            ->orderBy('invoices.due_date', 'asc')
            ->limit(5)
            ->get();
    }

    protected function getNotifications($parentId)
    {
        return DB::table('notifications')
            ->where('user_id', $parentId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    protected function getChildGrades($childId)
    {
        return DB::table('grades')
            ->join('classes', 'grades.class_id', '=', 'classes.id')
            ->where('grades.student_id', $childId)
            ->select('grades.*', 'classes.name as class_name')
            ->orderBy('grades.created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getChildAttendance($childId)
    {
        $total = DB::table('attendances')
            ->where('student_id', $childId)
            ->count();

        $present = DB::table('attendances')
            ->where('student_id', $childId)
            ->where('status', 'present')
            ->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $total - $present,
            'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
        ];
    }

    protected function getChildAssignments($childId)
    {
        return DB::table('assignments')
            ->join('class_enrollments', 'assignments.class_id', '=', 'class_enrollments.class_id')
            ->leftJoin('assignment_submissions', function ($join) use ($childId) {
                $join->on('assignments.id', '=', 'assignment_submissions.assignment_id')
                    ->where('assignment_submissions.student_id', '=', $childId);
            })
            ->where('class_enrollments.student_id', $childId)
            ->select('assignments.*', 'assignment_submissions.status as submission_status')
            ->orderBy('assignments.due_date', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getChildProgress($childId)
    {
        return DB::table('student_progress')
            ->where('student_id', $childId)
            ->first() ?? (object) [
                'completion_rate' => 0,
                'attendance_rate' => 0,
                'grade_average' => 0,
            ];
    }
}
