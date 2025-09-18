<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
        'email_verified_at',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

    // Relationships
    public function createdProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class);
    }

    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'user_favorites')
                    ->withPivot(['price_alert_enabled', 'target_price'])
                    ->withTimestamps();
    }

    public function pushNotificationTokens(): HasMany
    {
        return $this->hasMany(PushNotificationToken::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->role($role);
    }

    // Accessors
    public function getUnreadNotificationsCountAttribute()
    {
        return $this->notifications()->unread()->count();
    }

    public function getFavoritesCountAttribute()
    {
        return $this->favorites()->count();
    }
}