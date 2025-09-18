<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'key',
        'permissions',
        'rate_limit_per_hour',
        'is_active',
        'expires_at',
        'last_used_at',
        'usage_count',
        'allowed_ips',
        'allowed_domains'
    ];

    protected $casts = [
        'permissions' => 'array',
        'allowed_ips' => 'array',
        'allowed_domains' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'rate_limit_per_hour' => 'integer'
    ];

    protected $hidden = [
        'key'
    ];

    /**
     * Generate a new API key
     */
    public static function generateKey(): string
    {
        return 'kac_' . Str::random(40);
    }

    /**
     * Create a new API key for user
     */
    public static function createForUser(int $userId, array $data): self
    {
        return self::create([
            'user_id' => $userId,
            'name' => $data['name'],
            'key' => self::generateKey(),
            'permissions' => $data['permissions'] ?? [],
            'rate_limit_per_hour' => $data['rate_limit_per_hour'] ?? 1000,
            'expires_at' => $data['expires_at'] ?? null,
            'allowed_ips' => $data['allowed_ips'] ?? [],
            'allowed_domains' => $data['allowed_domains'] ?? [],
            'is_active' => true
        ]);
    }

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if API key has permission
     */
    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true; // No restrictions
        }

        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }

    /**
     * Check if IP is allowed
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true; // No IP restrictions
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Check if domain is allowed
     */
    public function isDomainAllowed(string $domain): bool
    {
        if (empty($this->allowed_domains)) {
            return true; // No domain restrictions
        }

        return in_array($domain, $this->allowed_domains);
    }

    /**
     * Get masked key for display
     */
    public function getMaskedKeyAttribute(): string
    {
        return substr($this->key, 0, 8) . str_repeat('*', 32);
    }

    /**
     * Check if key is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Revoke API key
     */
    public function revoke(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Regenerate API key
     */
    public function regenerate(): string
    {
        $newKey = self::generateKey();
        $this->update([
            'key' => $newKey,
            'usage_count' => 0,
            'last_used_at' => null
        ]);
        
        return $newKey;
    }

    /**
     * Scope for active keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for user keys
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
