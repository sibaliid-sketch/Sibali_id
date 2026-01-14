<?php

namespace App\Models\Academic;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'content',
        'attachments',
        'submitted_at',
        'grade',
        'feedback',
        'graded_by',
        'graded_at',
        'status',
        'plagiarism_score',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'attachments' => 'array',
        'plagiarism_score' => 'float',
    ];

    /**
     * Get the assignment this submission belongs to
     */
    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    /**
     * Get the student who submitted
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the grader
     */
    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Scope a query to only include graded submissions
     */
    public function scopeGraded($query)
    {
        return $query->whereNotNull('grade')
            ->whereNotNull('graded_at');
    }

    /**
     * Scope a query to only include pending submissions
     */
    public function scopePending($query)
    {
        return $query->whereNull('grade')
            ->where('status', 'submitted');
    }

    /**
     * Scope a query to only include late submissions
     */
    public function scopeLate($query)
    {
        return $query->whereHas('assignment', function ($q) {
            $q->whereRaw('assignment_submissions.submitted_at > assignments.due_date');
        });
    }

    /**
     * Check if submission is late
     */
    public function isLate(): bool
    {
        if (! $this->submitted_at || ! $this->assignment) {
            return false;
        }

        return $this->submitted_at > $this->assignment->due_date;
    }

    /**
     * Check if submission is graded
     */
    public function isGraded(): bool
    {
        return $this->grade !== null && $this->graded_at !== null;
    }

    /**
     * Get grade percentage
     */
    public function getGradePercentageAttribute(): ?float
    {
        if ($this->grade === null || ! $this->assignment) {
            return null;
        }

        $maxPoints = $this->assignment->points ?? 100;

        return round(($this->grade / $maxPoints) * 100, 2);
    }

    /**
     * Get grade letter
     */
    public function getGradeLetterAttribute(): ?string
    {
        $percentage = $this->grade_percentage;

        if ($percentage === null) {
            return null;
        }

        if ($percentage >= 90) {
            return 'A';
        }
        if ($percentage >= 80) {
            return 'B';
        }
        if ($percentage >= 70) {
            return 'C';
        }
        if ($percentage >= 60) {
            return 'D';
        }

        return 'E';
    }

    /**
     * Get days late
     */
    public function getDaysLateAttribute(): int
    {
        if (! $this->isLate()) {
            return 0;
        }

        return $this->assignment->due_date->diffInDays($this->submitted_at);
    }

    /**
     * Check if plagiarism detected
     */
    public function hasPlagiarism(): bool
    {
        return $this->plagiarism_score !== null && $this->plagiarism_score > 30;
    }
}
