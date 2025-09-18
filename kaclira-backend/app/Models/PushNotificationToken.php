<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_id',
        'endpoint',
        'p256dh',
        'auth',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get the user that owns the push notification token
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active tokens
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific platform
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
