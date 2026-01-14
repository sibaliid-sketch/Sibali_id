<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicStoreRequest;
use App\Services\AcademicService;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    protected $academicService;

    public function __construct(AcademicService $academicService)
    {
        $this->academicService = $academicService;
        $this->middleware(['auth', 'role:admin|supervisor']);
    }

    public function index(Request $request)
    {
        $academics = $this->academicService->getAcademics($request->all());

        return response()->json([
            'success' => true,
            'data' => $academics,
        ]);
    }

    public function store(AcademicStoreRequest $request)
    {
        $academic = $this->academicService->createAcademic($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Academic entity created successfully',
            'data' => $academic,
        ], 201);
    }

    public function show($id)
    {
        $academic = $this->academicService->getAcademicById($id);

        if (! $academic) {
            return response()->json([
                'success' => false,
                'message' => 'Academic entity not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $academic,
        ]);
    }

    public function update(AcademicStoreRequest $request, $id)
    {
        $academic = $this->academicService->updateAcademic($id, $request->validated());

        if (! $academic) {
            return response()->json([
                'success' => false,
                'message' => 'Academic entity not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Academic entity updated successfully',
            'data' => $academic,
        ]);
    }

    public function destroy($id)
    {
        $deleted = $this->academicService->deleteAcademic($id);

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Academic entity not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Academic entity deleted successfully',
        ]);
    }

    public function assignTeacher(Request $request, $id)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
        ]);

        $result = $this->academicService->assignTeacher($id, $request->teacher_id);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign teacher',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher assigned successfully',
        ]);
    }
}
