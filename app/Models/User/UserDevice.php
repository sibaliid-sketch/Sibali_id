<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_hash',
        'ip_address',
        'user_agent',
        'platform',
        'browser',
        'device_type',
        'is_trusted',
        'last_seen_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get the user that owns the device
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Scope a query to only include trusted devices
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    /**
     * Scope a query to only include devices seen recently
     */
    public function scopeRecentlySeen($query, $days = 30)
    {
        return $query->where('last_seen_at', '>=', now()->subDays($days));
    }

    /**
     * Check if device is active (seen within last 30 days)
     */
    public function isActive(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->isAfter(now()->subDays(30));
    }

    /**
     * Get device info as array
     */
    public function getDeviceInfo(): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'browser' => $this->browser,
            'device_type' => $this->device_type,
            'is_trusted' => $this->is_trusted,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'ip_address' => $this->ip_address,
        ];
    }
}
