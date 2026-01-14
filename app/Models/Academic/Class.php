<?php

namespace App\Models\Academic;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'code',
        'description',
        'teacher_id',
        'max_students',
        'status',
        'location',
        'start_date',
        'end_date',
        'schedule',
        'meta',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'schedule' => 'array',
        'meta' => 'array',
    ];

    /**
     * Get the teacher for this class
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the students enrolled in this class
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'class_enrollments', 'class_id', 'student_id')
            ->withPivot('enrolled_at', 'status', 'completion_rate')
            ->withTimestamps();
    }

    /**
     * Get the assignments for this class
     */
    public function assignments()
    {
        return $this->hasMany(\App\Models\Academic\Assignment::class, 'class_id');
    }

    /**
     * Get the materials for this class
     */
    public function materials()
    {
        return $this->hasMany(\App\Models\Academic\Material::class, 'class_id');
    }

    /**
     * Scope a query to only include active classes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope a query to only include upcoming classes
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now())
            ->where('status', 'active');
    }

    /**
     * Scope a query to only include completed classes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed')
            ->orWhere(function ($q) {
                $q->where('end_date', '<', now());
            });
    }

    /**
     * Check if class is full
     */
    public function isFull(): bool
    {
        return $this->students()->count() >= $this->max_students;
    }

    /**
     * Get available seats
     */
    public function getAvailableSeatsAttribute(): int
    {
        return max(0, $this->max_students - $this->students()->count());
    }

    /**
     * Get enrollment count
     */
    public function getEnrollmentCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Check if class is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date <= now()
            && $this->end_date >= now();
    }

    /**
     * Get formatted schedule
     */
    public function getFormattedScheduleAttribute(): string
    {
        if (! $this->schedule || ! is_array($this->schedule)) {
            return 'Tidak ada jadwal';
        }

        $days = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];

        $formatted = [];
        foreach ($this->schedule as $schedule) {
            $day = $days[$schedule['day']] ?? $schedule['day'];
            $formatted[] = "{$day} {$schedule['start_time']} - {$schedule['end_time']}";
        }

        return implode(', ', $formatted);
    }
}
