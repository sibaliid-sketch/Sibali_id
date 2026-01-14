<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HRService;
use Illuminate\Http\Request;

class HRController extends Controller
{
    protected $hrService;

    public function __construct(HRService $hrService)
    {
        $this->hrService = $hrService;
        $this->middleware(['auth', 'role:admin|hr_admin']);
    }

    public function employees(Request $request)
    {
        $employees = $this->hrService->getEmployees($request->all());

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    public function storeEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:employees,email',
            'department_id' => 'required|uuid|exists:departments,id',
            'position' => 'required|string',
            'join_date' => 'required|date',
            'staff_level' => 'required|integer|min:1|max:6',
        ]);

        $employee = $this->hrService->createEmployee($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully',
            'data' => $employee,
        ], 201);
    }

    public function updateEmployee(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:150',
            'email' => 'sometimes|email|unique:employees,email,'.$id,
            'department_id' => 'sometimes|uuid|exists:departments,id',
            'position' => 'sometimes|string',
            'staff_level' => 'sometimes|integer|min:1|max:6',
        ]);

        $employee = $this->hrService->updateEmployee($id, $request->all());

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
            'data' => $employee,
        ]);
    }

    public function approveLeave(Request $request, $leaveId)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string',
        ]);

        $leave = $this->hrService->processLeaveRequest($leaveId, $request->all());

        if (! $leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Leave request processed successfully',
            'data' => $leave,
        ]);
    }

    public function triggerPayroll(Request $request)
    {
        $request->validate([
            'period' => 'required|string',
            'dry_run' => 'boolean',
        ]);

        $result = $this->hrService->triggerPayrollRun($request->all());

        return response()->json([
            'success' => true,
            'message' => $request->dry_run ? 'Payroll preview generated' : 'Payroll run initiated',
            'data' => $result,
        ]);
    }

    public function assignRole(Request $request, $employeeId)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $result = $this->hrService->assignEmployeeRole($employeeId, $request->role_id);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign role',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully',
        ]);
    }

    public function employeePerformance($employeeId)
    {
        $performance = $this->hrService->getEmployeePerformance($employeeId);

        if (! $performance) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }
}
