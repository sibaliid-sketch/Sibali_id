<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'invoice_number',
        'items',
        'subtotal',
        'tax',
        'discount',
        'total',
        'due_date',
        'status',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the student for this invoice
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the payments for this invoice
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    /**
     * Scope a query to only include pending invoices
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->whereNull('paid_at');
    }

    /**
     * Scope a query to only include paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid')
                    ->whereNotNull('paid_at');
    }

    /**
     * Scope a query to only include overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                    ->where('due_date', '<', now());
    }

    /**
     * Scope a query to only include cancelled invoices
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' && $this->paid_at !== null;
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date < now();
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
     * Get days overdue
     */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    /**
     * Get formatted subtotal
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    /**
     * Get formatted tax
     */
    public function getFormattedTaxAttribute(): string
    {
        return 'Rp ' . number_format($this->tax, 0, ',', '.');
    }

    /**
     * Get formatted discount
     */
    public function getFormattedDiscountAttribute(): string
    {
        return 'Rp ' . number_format($this->discount, 0, ',', '.');
    }

    /**
     * Get total paid amount
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()
                    ->where('status', 'verified')
                    ->sum('amount');
    }

    /**
     * Get remaining amount
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total - $this->total_paid);
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">Belum Dibayar</span>',
            'paid' => '<span class="badge badge-success">Lunas</span>',
            'partial' => '<span class="badge badge-info">Dibayar Sebagian</span>',
            'overdue' => '<span class="badge badge-danger">Jatuh Tempo</span>',
            'cancelled' => '<span class="badge badge-secondary">Dibatalkan</span>',
        ];

        $status = $this->status;
        if ($this->isOverdue()) {
            $status = 'overdue';
        }

        return $badges[$status] ?? '<span class="badge">' . ucfirst($status) . '</span>';
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(): bool
    {
        $this->status = 'paid';
        $this->paid_at = now();

        return $this->save();
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled($reason = null): bool
    {
        $this->status = 'cancelled';

        if ($reason) {
            $this->notes = $reason;
        }

        return $this->save();
    }

    /**
     * Calculate late fee
     */
    public function calculateLateFee(): float
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        // 1% per day, max 10%
        $daysOverdue = $this->days_overdue;
        $feePercentage = min($daysOverdue * 1, 10);

        return round($this->total * ($feePercentage / 100), 2);
    }
}
