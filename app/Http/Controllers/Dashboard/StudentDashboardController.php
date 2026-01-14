<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StudentDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'user.type:student']);
    }

    public function index()
    {
        $studentId = auth()->id();

        $data = [
            'student' => $this->getStudentProfile($studentId),
            'stats' => $this->getStudentStats($studentId),
            'upcoming_classes' => $this->getUpcomingClasses($studentId),
            'assignments' => $this->getAssignments($studentId),
            'recent_grades' => $this->getRecentGrades($studentId),
            'payments' => $this->getPayments($studentId),
            'notifications' => $this->getNotifications($studentId),
            'progress' => $this->getProgress($studentId),
        ];

        return view('dashboard.student.index', $data);
    }

    public function classes()
    {
        $studentId = auth()->id();

        $classes = DB::table('class_enrollments')
            ->join('classes', 'class_enrollments.class_id', '=', 'classes.id')
            ->join('users as teachers', 'classes.teacher_id', '=', 'teachers.id')
            ->where('class_enrollments.student_id', $studentId)
            ->select(
                'classes.*',
                'teachers.name as teacher_name',
                'class_enrollments.enrolled_at',
                'class_enrollments.status as enrollment_status',
                'class_enrollments.completion_rate'
            )
            ->orderBy('classes.start_date', 'desc')
            ->paginate(10);

        return view('dashboard.student.classes', compact('classes'));
    }

    public function assignments()
    {
        $studentId = auth()->id();

        $assignments = DB::table('assignments')
            ->join('class_enrollments', 'assignments.class_id', '=', 'class_enrollments.class_id')
            ->join('classes', 'assignments.class_id', '=', 'classes.id')
            ->leftJoin('assignment_submissions', function($join) use ($studentId) {
                $join->on('assignments.id', '=', 'assignment_submissions.assignment_id')
                     ->where('assignment_submissions.student_id', '=', $studentId);
            })
            ->where('class_enrollments.student_id', $studentId)
            ->select(
                'assignments.*',
                'classes.name as class_name',
                'assignment_submissions.id as submission_id',
                'assignment_submissions.status as submission_status',
                'assignment_submissions.grade',
                'assignment_submissions.submitted_at'
            )
            ->orderBy('assignments.due_date', 'desc')
            ->paginate(15);

        return view('dashboard.student.assignments', compact('assignments'));
    }

    public function grades()
    {
        $studentId = auth()->id();

        $grades = DB::table('grades')
            ->join('classes', 'grades.class_id', '=', 'classes.id')
            ->where('grades.student_id', $studentId)
            ->select('grades.*', 'classes.name as class_name')
            ->orderBy('grades.created_at', 'desc')
            ->paginate(15);

        $summary = $this->getGradeSummary($studentId);

        return view('dashboard.student.grades', compact('grades', 'summary'));
    }

    public function payments()
    {
        $studentId = auth()->id();

        $payments = DB::table('payments')
            ->leftJoin('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.student_id', $studentId)
            ->select('payments.*', 'invoices.invoice_number', 'invoices.total as invoice_total')
            ->orderBy('payments.created_at', 'desc')
            ->paginate(15);

        $summary = $this->getPaymentSummary($studentId);

        return view('dashboard.student.payments', compact('payments', 'summary'));
    }

    protected function getStudentProfile($studentId)
    {
        return DB::table('students')
            ->join('users', 'students.id', '=', 'users.id')
            ->where('students.id', $studentId)
            ->select('users.*', 'students.*')
            ->first();
    }

    protected function getStudentStats($studentId)
    {
        return [
            'active_classes' => DB::table('class_enrollments')
                ->join('classes', 'class_enrollments.class_id', '=', 'classes.id')
                ->where('class_enrollments.student_id', $studentId)
                ->where('classes.status', 'active')
                ->count(),
            'pending_assignments' => DB::table('assignments')
                ->join('class_enrollments', 'assignments.class_id', '=', 'class_enrollments.class_id')
                ->leftJoin('assignment_submissions', function($join) use ($studentId) {
                    $join->on('assignments.id', '=', 'assignment_submissions.assignment_id')
                         ->where('assignment_submissions.student_id', '=', $studentId);
                })
                ->where('class_enrollments.student_id', $studentId)
                ->where('assignments.status', 'active')
                ->whereNull('assignment_submissions.id')
                ->count(),
            'average_grade' => DB::table('grades')
                ->where('student_id', $studentId)
                ->avg('grade_value') ?? 0,
            'attendance_rate' => $this->calculateAttendanceRate($studentId),
        ];
    }

    protected function getUpcomingClasses($studentId)
    {
        return DB::table('class_enrollments')
            ->join('classes', 'class_enrollments.class_id', '=', 'classes.id')
            ->join('users as teachers', 'classes.teacher_id', '=', 'teachers.id')
            ->where('class_enrollments.student_id', $studentId)
            ->where('classes.status', 'active')
            ->select(
                'classes.*',
                'teachers.name as teacher_name',
                'class_enrollments.enrolled_at'
            )
            ->orderBy('classes.start_date', 'asc')
            ->limit(5)
            ->get();
    }

    protected function getAssignments($studentId)
    {
        return DB::table('assignments')
            ->join('class_enrollments', 'assignments.class_id', '=', 'class_enrollments.class_id')
            ->join('classes', 'assignments.class_id', '=', 'classes.id')
            ->leftJoin('assignment_submissions', function($join) use ($studentId) {
                $join->on('assignments.id', '=', 'assignment_submissions.assignment_id')
                     ->where('assignment_submissions.student_id', '=', $studentId);
            })
            ->where('class_enrollments.student_id', $studentId)
            ->where('assignments.status', 'active')
            ->select(
                'assignments.*',
                'classes.name as class_name',
                'assignment_submissions.status as submission_status'
            )
            ->orderBy('assignments.due_date', 'asc')
            ->limit(5)
            ->get();
    }

    protected function getRecentGrades($studentId)
    {
        return DB::table('grades')
            ->join('classes', 'grades.class_id', '=', 'classes.id')
            ->where('grades.student_id', $studentId)
            ->select('grades.*', 'classes.name as class_name')
            ->orderBy('grades.created_at', 'desc')
            ->limit(5)
            ->get();
    }

    protected function getPayments($studentId)
    {
        return DB::table('payments')
            ->leftJoin('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.student_id', $studentId)
            ->select('payments.*', 'invoices.invoice_number')
            ->orderBy('payments.created_at', 'desc')
            ->limit(5)
            ->get();
    }

    protected function getNotifications($studentId)
    {
        return DB::table('notifications')
            ->where('user_id', $studentId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    protected function getProgress($studentId)
    {
        $progress = DB::table('student_progress')
            ->where('student_id', $studentId)
            ->first();

        if (!$progress) {
            return [
                'completion_rate' => 0,
                'attendance_rate' => 0,
                'grade_average' => 0,
                'total_hours' => 0,
            ];
        }

        return (array) $progress;
    }

    protected function calculateAttendanceRate($studentId)
    {
        $total = DB::table('attendances')
            ->where('student_id', $studentId)
            ->count();

        if ($total === 0) {
            return 0;
        }

        $present = DB::table('attendances')
            ->where('student_id', $studentId)
            ->where('status', 'present')
            ->count();

        return round(($present / $total) * 100, 1);
    }

    protected function getGradeSummary($studentId)
    {
        $grades = DB::table('grades')
            ->where('student_id', $studentId)
            ->get();

        if ($grades->isEmpty()) {
            return [
                'average' => 0,
                'highest' => 0,
                'lowest' => 0,
                'total' => 0,
            ];
        }

        return [
            'average' => round($grades->avg('grade_value'), 2),
            'highest' => $grades->max('grade_value'),
            'lowest' => $grades->min('grade_value'),
            'total' => $grades->count(),
        ];
    }

    protected function getPaymentSummary($studentId)
    {
        return [
            'total_paid' => DB::table('payments')
                ->where('student_id', $studentId)
                ->where('status', 'verified')
                ->sum('amount'),
            'pending' => DB::table('payments')
                ->where('student_id', $studentId)
                ->where('status', 'pending')
                ->sum('amount'),
            'total_invoices' => DB::table('invoices')
                ->where('student_id', $studentId)
                ->count(),
        ];
    }
}
