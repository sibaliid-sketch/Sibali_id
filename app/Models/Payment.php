<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'invoice_id',
        'method',
        'amount',
        'currency',
        'status',
        'proof_url',
        'verified_by',
        'verified_at',
        'transaction_id',
        'gateway_response',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'gateway_response' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the student who made this payment
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the invoice for this payment
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Get the verifier
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the payment proof
     */
    public function proof()
    {
        return $this->hasOne(PaymentProof::class, 'payment_id');
    }

    /**
     * Scope a query to only include pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include submitted payments
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted')
                    ->whereNull('verified_at');
    }

    /**
     * Scope a query to only include verified payments
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified')
                    ->whereNotNull('verified_at');
    }

    /**
     * Scope a query to only include rejected payments
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if payment is verified
     */
    public function isVerified(): bool
    {
        return $this->status === 'verified' && $this->verified_at !== null;
    }

    /**
     * Check if payment is pending verification
     */
    public function isPendingVerification(): bool
    {
        return $this->status === 'submitted' && $this->verified_at === null;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get payment method label
     */
    public function getMethodLabelAttribute(): string
    {
        $methods = [
            'qris' => 'QRIS',
            'bank_transfer' => 'Transfer Bank',
            'virtual_account' => 'Virtual Account',
            'credit_card' => 'Kartu Kredit',
            'e_wallet' => 'E-Wallet',
            'cash' => 'Tunai',
        ];

        return $methods[$this->method] ?? ucfirst($this->method);
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'submitted' => '<span class="badge badge-info">Menunggu Verifikasi</span>',
            'verified' => '<span class="badge badge-success">Terverifikasi</span>',
            'rejected' => '<span class="badge badge-danger">Ditolak</span>',
            'refunded' => '<span class="badge badge-secondary">Dikembalikan</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge">' . ucfirst($this->status) . '</span>';
    }

    /**
     * Get days since submission
     */
    public function getDaysSinceSubmissionAttribute(): ?int
    {
        if ($this->status !== 'submitted' || !$this->created_at) {
            return null;
        }

        return now()->diffInDays($this->created_at);
    }

    /**
     * Mark as verified
     */
    public function markAsVerified($verifierId, $notes = null): bool
    {
        $this->status = 'verified';
        $this->verified_by = $verifierId;
        $this->verified_at = now();

        if ($notes) {
            $this->notes = $notes;
        }

        return $this->save();
    }

    /**
     * Mark as rejected
     */
    public function markAsRejected($verifierId, $reason): bool
    {
        $this->status = 'rejected';
        $this->verified_by = $verifierId;
        $this->verified_at = now();
        $this->notes = $reason;

        return $this->save();
    }
}
