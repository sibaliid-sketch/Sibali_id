<?php

namespace App\Services;

use App\Repositories\EmployeeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class HRService
{
    protected $employeeRepository;

    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function getEmployees(array $filters = [])
    {
        return $this->employeeRepository->getAll($filters);
    }

    public function createEmployee(array $data)
    {
        try {
            DB::beginTransaction();

            // Encrypt sensitive PII data
            if (isset($data['email'])) {
                $data['email_encrypted'] = encrypt($data['email']);
            }

            // Generate employee ID
            $data['employee_id'] = $this->generateEmployeeId();

            // Set default password (should be changed on first login)
            $data['password'] = Hash::make('Welcome123!');
            $data['must_change_password'] = true;

            $employee = $this->employeeRepository->create($data);

            // Create user account
            $this->createUserAccount($employee);

            Log::info('Employee created', [
                'employee_id' => $employee->id,
                'name' => $employee->name,
            ]);

            DB::commit();

            return $employee;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create employee', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function updateEmployee($id, array $data)
    {
        try {
            DB::beginTransaction();

            // Encrypt email if updated
            if (isset($data['email'])) {
                $data['email_encrypted'] = encrypt($data['email']);
            }

            $employee = $this->employeeRepository->update($id, $data);

            if ($employee) {
                Log::info('Employee updated', ['employee_id' => $id]);
            }

            DB::commit();

            return $employee;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update employee', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function processLeaveRequest($leaveId, array $data)
    {
        try {
            DB::beginTransaction();

            $leave = $this->employeeRepository->findLeaveRequest($leaveId);

            if (! $leave) {
                return null;
            }

            $leave->status = $data['status'];
            $leave->approved_by = auth()->id();
            $leave->approved_at = now();
            $leave->notes = $data['notes'] ?? null;
            $leave->save();

            // Send notification to employee
            $this->notifyLeaveStatus($leave);

            Log::info('Leave request processed', [
                'leave_id' => $leaveId,
                'status' => $data['status'],
            ]);

            DB::commit();

            return $leave;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process leave request', [
                'leave_id' => $leaveId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function triggerPayrollRun(array $data)
    {
        try {
            $period = $data['period'];
            $dryRun = $data['dry_run'] ?? false;

            $employees = $this->employeeRepository->getActiveEmployees();

            $payrollData = [];
            foreach ($employees as $employee) {
                $payrollData[] = $this->calculatePayroll($employee, $period);
            }

            if (! $dryRun) {
                DB::beginTransaction();

                foreach ($payrollData as $payroll) {
                    $this->employeeRepository->createPayrollRecord($payroll);
                }

                Log::info('Payroll run completed', [
                    'period' => $period,
                    'employee_count' => count($payrollData),
                ]);

                DB::commit();
            }

            return [
                'period' => $period,
                'dry_run' => $dryRun,
                'employee_count' => count($payrollData),
                'total_amount' => array_sum(array_column($payrollData, 'net_salary')),
                'details' => $payrollData,
            ];
        } catch (\Exception $e) {
            if (! $dryRun) {
                DB::rollBack();
            }
            Log::error('Failed to trigger payroll run', [
                'period' => $data['period'],
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function assignEmployeeRole($employeeId, $roleId)
    {
        try {
            DB::beginTransaction();

            $result = $this->employeeRepository->assignRole($employeeId, $roleId);

            if ($result) {
                Log::info('Role assigned to employee', [
                    'employee_id' => $employeeId,
                    'role_id' => $roleId,
                ]);
            }

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign role', [
                'employee_id' => $employeeId,
                'role_id' => $roleId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getEmployeePerformance($employeeId)
    {
        $employee = $this->employeeRepository->findById($employeeId);

        if (! $employee) {
            return null;
        }

        return [
            'employee' => $employee,
            'attendance_rate' => $this->calculateAttendanceRate($employeeId),
            'completed_tasks' => $this->getCompletedTasksCount($employeeId),
            'performance_reviews' => $this->getPerformanceReviews($employeeId),
            'training_completed' => $this->getCompletedTrainings($employeeId),
        ];
    }

    protected function generateEmployeeId()
    {
        $prefix = 'EMP';
        $year = date('Y');
        $lastId = $this->employeeRepository->getLastEmployeeNumber($year);
        $number = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$year}{$number}";
    }

    protected function createUserAccount($employee)
    {
        // Create user account for employee login
        DB::table('users')->insert([
            'name' => $employee->name,
            'email' => $employee->email,
            'password' => $employee->password,
            'user_type' => 'staff',
            'employee_id' => $employee->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function calculatePayroll($employee, $period)
    {
        $baseSalary = $employee->base_salary ?? 0;
        $allowances = $this->calculateAllowances($employee);
        $deductions = $this->calculateDeductions($employee, $period);

        $grossSalary = $baseSalary + $allowances;
        $netSalary = $grossSalary - $deductions;

        return [
            'employee_id' => $employee->id,
            'period' => $period,
            'base_salary' => $baseSalary,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'gross_salary' => $grossSalary,
            'net_salary' => $netSalary,
        ];
    }

    protected function calculateAllowances($employee)
    {
        // Placeholder for allowance calculation
        return 0;
    }

    protected function calculateDeductions($employee, $period)
    {
        // Placeholder for deduction calculation (tax, insurance, etc.)
        return 0;
    }

    protected function notifyLeaveStatus($leave)
    {
        // Placeholder for notification
    }

    protected function calculateAttendanceRate($employeeId)
    {
        // Placeholder
        return 95.5;
    }

    protected function getCompletedTasksCount($employeeId)
    {
        // Placeholder
        return 0;
    }

    protected function getPerformanceReviews($employeeId)
    {
        // Placeholder
        return [];
    }

    protected function getCompletedTrainings($employeeId)
    {
        // Placeholder
        return [];
    }
}
