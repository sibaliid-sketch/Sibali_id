<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RetentionService
{
    public function getAtRiskStudents(array $filters = [])
    {
        $query = DB::table('students')
            ->join('users', 'students.id', '=', 'users.id')
            ->leftJoin('student_progress', 'students.id', '=', 'student_progress.student_id')
            ->select('students.*', 'users.name', 'users.email', 'student_progress.risk_score')
            ->where('student_progress.risk_score', '>=', config('engagement.risk_threshold', 70));

        if (isset($filters['min_score'])) {
            $query->where('student_progress.risk_score', '>=', $filters['min_score']);
        }

        if (isset($filters['max_score'])) {
            $query->where('student_progress.risk_score', '<=', $filters['max_score']);
        }

        return $query->orderBy('student_progress.risk_score', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function calculateRiskScore($studentId)
    {
        try {
            $student = DB::table('students')->where('id', $studentId)->first();

            if (! $student) {
                return null;
            }

            // Get metrics
            $attendanceRate = $this->getAttendanceRate($studentId);
            $gradeAverage = $this->getGradeAverage($studentId);
            $lastActivity = $this->getLastActivityDays($studentId);
            $engagementScore = $this->getEngagementScore($studentId);

            // Calculate risk score (0-100, higher = more at risk)
            $riskScore = 0;

            // Attendance factor (40%)
            if ($attendanceRate < 70) {
                $riskScore += 40;
            } elseif ($attendanceRate < 85) {
                $riskScore += 20;
            }

            // Grade factor (30%)
            if ($gradeAverage < 60) {
                $riskScore += 30;
            } elseif ($gradeAverage < 75) {
                $riskScore += 15;
            }

            // Activity factor (20%)
            if ($lastActivity > 14) {
                $riskScore += 20;
            } elseif ($lastActivity > 7) {
                $riskScore += 10;
            }

            // Engagement factor (10%)
            if ($engagementScore < 30) {
                $riskScore += 10;
            } elseif ($engagementScore < 50) {
                $riskScore += 5;
            }

            // Update risk score in DB
            DB::table('student_progress')->updateOrInsert(
                ['student_id' => $studentId],
                [
                    'risk_score' => $riskScore,
                    'attendance_rate' => $attendanceRate,
                    'grade_average' => $gradeAverage,
                    'last_activity_days' => $lastActivity,
                    'engagement_score' => $engagementScore,
                    'updated_at' => now(),
                ]
            );

            return [
                'student_id' => $studentId,
                'risk_score' => $riskScore,
                'risk_level' => $this->getRiskLevel($riskScore),
                'factors' => [
                    'attendance_rate' => $attendanceRate,
                    'grade_average' => $gradeAverage,
                    'last_activity_days' => $lastActivity,
                    'engagement_score' => $engagementScore,
                ],
                'recommendations' => $this->getRecommendations($riskScore),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to calculate risk score', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function triggerIntervention($studentId, array $data)
    {
        try {
            DB::beginTransaction();

            $intervention = DB::table('retention_interventions')->insertGetId([
                'student_id' => $studentId,
                'type' => $data['intervention_type'],
                'message' => $data['message'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? now(),
                'status' => 'pending',
                'triggered_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Queue notification
            $this->queueInterventionNotification($intervention, $data);

            Log::info('Intervention triggered', [
                'intervention_id' => $intervention,
                'student_id' => $studentId,
                'type' => $data['intervention_type'],
            ]);

            DB::commit();

            return $intervention;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to trigger intervention', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getInterventionHistory($studentId)
    {
        return DB::table('retention_interventions')
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRetentionMetrics(array $filters = [])
    {
        $period = $filters['period'] ?? 'month';

        return [
            'at_risk_count' => $this->getAtRiskCount(),
            'intervention_success_rate' => $this->getInterventionSuccessRate($period),
            'churn_rate' => $this->getChurnRate($period),
            'retention_rate' => $this->getRetentionRate($period),
            'average_risk_score' => $this->getAverageRiskScore(),
        ];
    }

    protected function getAttendanceRate($studentId)
    {
        $total = DB::table('attendances')
            ->where('student_id', $studentId)
            ->count();

        if ($total == 0) {
            return 100;
        }

        $present = DB::table('attendances')
            ->where('student_id', $studentId)
            ->where('status', 'present')
            ->count();

        return round(($present / $total) * 100, 2);
    }

    protected function getGradeAverage($studentId)
    {
        return DB::table('grades')
            ->where('student_id', $studentId)
            ->avg('grade_value') ?? 0;
    }

    protected function getLastActivityDays($studentId)
    {
        $lastActivity = DB::table('activity_logs')
            ->where('user_id', $studentId)
            ->max('created_at');

        if (! $lastActivity) {
            return 999;
        }

        return now()->diffInDays($lastActivity);
    }

    protected function getEngagementScore($studentId)
    {
        // Placeholder - calculate based on logins, submissions, participation
        return 50;
    }

    protected function getRiskLevel($score)
    {
        if ($score >= 70) {
            return 'high';
        }
        if ($score >= 40) {
            return 'medium';
        }

        return 'low';
    }

    protected function getRecommendations($score)
    {
        $recommendations = [];

        if ($score >= 70) {
            $recommendations[] = 'Immediate intervention required';
            $recommendations[] = 'Schedule parent meeting';
            $recommendations[] = 'Assign academic advisor';
        } elseif ($score >= 40) {
            $recommendations[] = 'Monitor closely';
            $recommendations[] = 'Send engagement reminders';
        } else {
            $recommendations[] = 'Continue regular monitoring';
        }

        return $recommendations;
    }

    protected function queueInterventionNotification($interventionId, $data)
    {
        // Queue notification job
        Log::info('Queuing intervention notification', ['intervention_id' => $interventionId]);
    }

    protected function getAtRiskCount()
    {
        return DB::table('student_progress')
            ->where('risk_score', '>=', 70)
            ->count();
    }

    protected function getInterventionSuccessRate($period)
    {
        return 0; // Placeholder
    }

    protected function getChurnRate($period)
    {
        return 0; // Placeholder
    }

    protected function getRetentionRate($period)
    {
        return 0; // Placeholder
    }

    protected function getAverageRiskScore()
    {
        return DB::table('student_progress')->avg('risk_score') ?? 0;
    }
}
