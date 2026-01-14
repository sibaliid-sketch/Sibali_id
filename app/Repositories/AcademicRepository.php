<?php

namespace App\Repositories;

use App\Models\Academic\Class as AcademicClass;

class AcademicRepository
{
    public function getAll(array $filters = [])
    {
        $query = AcademicClass::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->with(['teacher', 'students'])
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById($id)
    {
        return AcademicClass::with(['teacher', 'students', 'schedule'])
            ->find($id);
    }

    public function create(array $data)
    {
        return AcademicClass::create($data);
    }

    public function update($id, array $data)
    {
        $academic = $this->findById($id);

        if (! $academic) {
            return null;
        }

        $academic->update($data);

        return $academic->fresh();
    }

    public function delete($id)
    {
        $academic = $this->findById($id);

        if (! $academic) {
            return false;
        }

        return $academic->delete();
    }

    public function assignTeacher($academicId, $teacherId)
    {
        $academic = $this->findById($academicId);

        if (! $academic) {
            return false;
        }

        $academic->teacher_id = $teacherId;

        return $academic->save();
    }

    public function checkEligibility($studentId, $classId)
    {
        $class = $this->findById($classId);

        if (! $class) {
            return false;
        }

        // Check capacity
        if ($class->students()->count() >= $class->max_students) {
            return false;
        }

        // Check if already enrolled
        if ($class->students()->where('student_id', $studentId)->exists()) {
            return false;
        }

        return true;
    }

    public function findScheduleConflicts(array $scheduleData)
    {
        return AcademicClass::where(function ($query) use ($scheduleData) {
            $query->where('teacher_id', $scheduleData['teacher_id'])
                ->orWhere('location', $scheduleData['location']);
        })
            ->where(function ($query) use ($scheduleData) {
                $query->whereBetween('start_time', [$scheduleData['start_time'], $scheduleData['end_time']])
                    ->orWhereBetween('end_time', [$scheduleData['start_time'], $scheduleData['end_time']]);
            })
            ->get();
    }
}
