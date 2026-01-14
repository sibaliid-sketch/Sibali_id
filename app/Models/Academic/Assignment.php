<?php

namespace App\Models\Academic;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_id',
        'title',
        'description',
        'instructions',
        'due_date',
        'points',
        'attachments',
        'type',
        'status',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'attachments' => 'array',
    ];

    /**
     * Get the class this assignment belongs to
     */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the creator of this assignment
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the submissions for this assignment
     */
    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }

    /**
     * Scope a query to only include active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('due_date', '>=', now());
    }

    /**
     * Scope a query to only include overdue assignments
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
            ->where('due_date', '<', now());
    }

    /**
     * Scope a query to only include upcoming assignments
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'active')
            ->where('due_date', '>', now())
            ->orderBy('due_date', 'asc');
    }

    /**
     * Check if assignment is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date < now() && $this->status === 'active';
    }

    /**
     * Get days until due
     */
    public function getDaysUntilDueAttribute(): int
    {
        if ($this->due_date < now()) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    /**
     * Get submission count
     */
    public function getSubmissionCountAttribute(): int
    {
        return $this->submissions()->count();
    }

    /**
     * Get graded submission count
     */
    public function getGradedCountAttribute(): int
    {
        return $this->submissions()->whereNotNull('grade')->count();
    }

    /**
     * Get average grade
     */
    public function getAverageGradeAttribute(): ?float
    {
        $graded = $this->submissions()->whereNotNull('grade')->get();

        if ($graded->isEmpty()) {
            return null;
        }

        return round($graded->avg('grade'), 2);
    }

    /**
     * Check if student has submitted
     */
    public function hasSubmission($studentId): bool
    {
        return $this->submissions()
            ->where('student_id', $studentId)
            ->exists();
    }

    /**
     * Get student's submission
     */
    public function getSubmission($studentId)
    {
        return $this->submissions()
            ->where('student_id', $studentId)
            ->first();
    }
}
