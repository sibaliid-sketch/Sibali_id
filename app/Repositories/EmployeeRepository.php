<?php

namespace App\Repositories;

use App\Models\HR\EmployeeRecord;
use Illuminate\Support\Facades\DB;

class EmployeeRepository
{
    public function getAll(array $filters = [])
    {
        $query = EmployeeRecord::query();

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['staff_level'])) {
            $query->where('staff_level', $filters['staff_level']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('employee_id', 'like', "%{$filters['search']}%");
            });
        }

        return $query->with(['department', 'role'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById($id)
    {
        return EmployeeRecord::with(['department', 'role', 'supervisor'])->find($id);
    }

    public function create(array $data)
    {
        return EmployeeRecord::create($data);
    }

    public function update($id, array $data)
    {
        $employee = $this->findById($id);

        if (! $employee) {
            return null;
        }

        $employee->update($data);

        return $employee->fresh();
    }

    public function getActiveEmployees()
    {
        return EmployeeRecord::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('employment_end_date')
                    ->orWhere('employment_end_date', '>', now());
            })
            ->get();
    }

    public function findLeaveRequest($leaveId)
    {
        return DB::table('leaves')->where('id', $leaveId)->first();
    }

    public function createPayrollRecord(array $data)
    {
        return DB::table('payroll_records')->insert(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function assignRole($employeeId, $roleId)
    {
        return DB::table('employee_roles')->updateOrInsert(
            ['employee_id' => $employeeId],
            ['role_id' => $roleId, 'updated_at' => now()]
        );
    }

    public function getLastEmployeeNumber($year)
    {
        $lastEmployee = EmployeeRecord::where('employee_id', 'like', "EMP{$year}%")
            ->orderBy('employee_id', 'desc')
            ->first();

        if (! $lastEmployee) {
            return 0;
        }

        return (int) substr($lastEmployee->employee_id, -4);
    }
}
