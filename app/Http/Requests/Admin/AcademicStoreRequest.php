<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AcademicStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->user_type, ['admin', 'supervisor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:classes,code',
            'description' => 'nullable|string',
            'teacher_id' => 'nullable|exists:users,id',
            'max_students' => 'required|integer|min:1|max:50',
            'status' => 'required|in:active,inactive,completed,cancelled',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'schedule' => 'nullable|array',
            'schedule.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedule.*.start_time' => 'required|date_format:H:i',
            'schedule.*.end_time' => 'required|date_format:H:i|after:schedule.*.start_time',
        ];

        // If updating, ignore current record for code uniqueness
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $academicId = $this->route('id');
            $rules['code'] = 'required|string|max:50|unique:classes,code,'.$academicId;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kelas wajib diisi',
            'code.required' => 'Kode kelas wajib diisi',
            'code.unique' => 'Kode kelas sudah digunakan',
            'teacher_id.exists' => 'Guru tidak ditemukan',
            'max_students.required' => 'Kapasitas maksimal siswa wajib diisi',
            'max_students.min' => 'Kapasitas minimal 1 siswa',
            'max_students.max' => 'Kapasitas maksimal 50 siswa',
            'status.required' => 'Status kelas wajib dipilih',
            'status.in' => 'Status kelas tidak valid',
            'start_date.required' => 'Tanggal mulai wajib diisi',
            'end_date.required' => 'Tanggal selesai wajib diisi',
            'end_date.after' => 'Tanggal selesai harus setelah tanggal mulai',
            'schedule.*.day.required' => 'Hari jadwal wajib dipilih',
            'schedule.*.start_time.required' => 'Waktu mulai wajib diisi',
            'schedule.*.end_time.required' => 'Waktu selesai wajib diisi',
            'schedule.*.end_time.after' => 'Waktu selesai harus setelah waktu mulai',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize code to uppercase
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->code)),
            ]);
        }

        // Trim name
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Check for schedule conflicts if teacher is assigned
        if ($this->has('teacher_id') && $this->teacher_id && $this->has('schedule')) {
            $this->checkScheduleConflicts();
        }
    }

    /**
     * Check for schedule conflicts
     */
    protected function checkScheduleConflicts(): void
    {
        // This would be implemented in the service layer
        // Here we just validate the data structure
    }
}
