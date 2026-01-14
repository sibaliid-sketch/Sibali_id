<?php

namespace App\Models\Academic;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_id',
        'title',
        'description',
        'type',
        'file_path',
        'file_url',
        'file_size',
        'mime_type',
        'version',
        'status',
        'access_policy',
        'uploaded_by',
        'download_count',
        'meta',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
        'download_count' => 'integer',
        'access_policy' => 'array',
        'meta' => 'array',
    ];

    /**
     * Get the class this material belongs to
     */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the uploader
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope a query to only include published materials
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include draft materials
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query by material type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes === null) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Get file extension
     */
    public function getFileExtensionAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    /**
     * Get file icon based on type
     */
    public function getFileIconAttribute(): string
    {
        $extension = $this->file_extension;

        $icons = [
            'pdf' => 'file-pdf',
            'doc' => 'file-word',
            'docx' => 'file-word',
            'xls' => 'file-excel',
            'xlsx' => 'file-excel',
            'ppt' => 'file-powerpoint',
            'pptx' => 'file-powerpoint',
            'jpg' => 'file-image',
            'jpeg' => 'file-image',
            'png' => 'file-image',
            'gif' => 'file-image',
            'mp4' => 'file-video',
            'avi' => 'file-video',
            'mov' => 'file-video',
            'mp3' => 'file-audio',
            'wav' => 'file-audio',
            'zip' => 'file-archive',
            'rar' => 'file-archive',
        ];

        return $icons[$extension] ?? 'file';
    }

    /**
     * Check if user can access this material
     */
    public function canAccess(User $user): bool
    {
        // If no access policy, allow all enrolled students
        if (! $this->access_policy || empty($this->access_policy)) {
            return $this->class->students()->where('student_id', $user->id)->exists();
        }

        // Check specific access rules
        if (isset($this->access_policy['allowed_roles'])) {
            if (! in_array($user->user_type, $this->access_policy['allowed_roles'])) {
                return false;
            }
        }

        if (isset($this->access_policy['allowed_users'])) {
            if (! in_array($user->id, $this->access_policy['allowed_users'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Check if material is downloadable
     */
    public function isDownloadable(): bool
    {
        return $this->status === 'published' && $this->file_path !== null;
    }
}
