<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'file_path',
        'file_url',
        'file_name',
        'file_size',
        'mime_type',
        'checksum',
        'uploaded_by',
        'verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the payment for this proof
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * Get the uploader
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the verifier
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if proof is verified
     */
    public function isVerified(): bool
    {
        return $this->verified && $this->verified_at !== null;
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

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get file extension
     */
    public function getFileExtensionAttribute(): ?string
    {
        if (!$this->file_name) {
            return null;
        }

        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Verify checksum
     */
    public function verifyChecksum(): bool
    {
        if (!$this->file_path || !$this->checksum) {
            return false;
        }

        $filePath = storage_path('app/' . $this->file_path);

        if (!file_exists($filePath)) {
            return false;
        }

        $calculatedChecksum = hash_file('sha256', $filePath);

        return $calculatedChecksum === $this->checksum;
    }

    /**
     * Mark as verified
     */
    public function markAsVerified($verifierId, $notes = null): bool
    {
        $this->verified = true;
        $this->verified_by = $verifierId;
        $this->verified_at = now();

        if ($notes) {
            $this->notes = $notes;
        }

        return $this->save();
    }

    /**
     * Get download URL
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('payment.proof.download', $this->id);
    }
}
