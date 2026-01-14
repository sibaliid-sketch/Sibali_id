<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RetentionService;
use Illuminate\Http\Request;

class StudentRetentionController extends Controller
{
    protected $retentionService;

    public function __construct(RetentionService $retentionService)
    {
        $this->retentionService = $retentionService;
        $this->middleware(['auth', 'role:admin|retention_manager']);
    }

    public function atRiskStudents(Request $request)
    {
        $students = $this->retentionService->getAtRiskStudents($request->all());

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }

    public function studentScore($studentId)
    {
        $score = $this->retentionService->calculateRiskScore($studentId);

        if (! $score) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $score,
        ]);
    }

    public function triggerIntervention(Request $request, $studentId)
    {
        $request->validate([
            'intervention_type' => 'required|in:email,sms,call,meeting',
            'message' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $intervention = $this->retentionService->triggerIntervention($studentId, $request->all());

        if (! $intervention) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger intervention',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Intervention triggered successfully',
            'data' => $intervention,
        ], 201);
    }

    public function interventionHistory($studentId)
    {
        $history = $this->retentionService->getInterventionHistory($studentId);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    public function retentionMetrics(Request $request)
    {
        $metrics = $this->retentionService->getRetentionMetrics($request->all());

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }
}
