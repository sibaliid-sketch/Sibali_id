<?php

namespace App\Services;

use App\Repositories\AcademicRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcademicService
{
    protected $academicRepository;

    public function __construct(AcademicRepository $academicRepository)
    {
        $this->academicRepository = $academicRepository;
    }

    public function getAcademics(array $filters = [])
    {
        return $this->academicRepository->getAll($filters);
    }

    public function getAcademicById($id)
    {
        return $this->academicRepository->findById($id);
    }

    public function createAcademic(array $data)
    {
        try {
            DB::beginTransaction();

            $academic = $this->academicRepository->create($data);

            // Log activity
            Log::info('Academic entity created', [
                'id' => $academic->id,
                'type' => $data['type'] ?? 'unknown',
            ]);

            DB::commit();

            return $academic;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create academic entity', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function updateAcademic($id, array $data)
    {
        try {
            DB::beginTransaction();

            $academic = $this->academicRepository->update($id, $data);

            if ($academic) {
                Log::info('Academic entity updated', ['id' => $id]);
            }

            DB::commit();

            return $academic;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update academic entity', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function deleteAcademic($id)
    {
        try {
            $result = $this->academicRepository->delete($id);

            if ($result) {
                Log::info('Academic entity deleted', ['id' => $id]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to delete academic entity', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function assignTeacher($academicId, $teacherId)
    {
        try {
            DB::beginTransaction();

            $result = $this->academicRepository->assignTeacher($academicId, $teacherId);

            if ($result) {
                Log::info('Teacher assigned to academic entity', [
                    'academic_id' => $academicId,
                    'teacher_id' => $teacherId,
                ]);
            }

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign teacher', [
                'academic_id' => $academicId,
                'teacher_id' => $teacherId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function checkEnrollmentEligibility($studentId, $classId)
    {
        return $this->academicRepository->checkEligibility($studentId, $classId);
    }

    public function getScheduleConflicts($scheduleData)
    {
        return $this->academicRepository->findScheduleConflicts($scheduleData);
    }
}
