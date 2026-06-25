<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'avatar',
        'role_id', 'is_banned', 'ban_reason', 'last_login_at', 'order_cancel_count',
        'tenant_id', 'is_super_admin',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'is_banned'         => 'boolean',
            'is_super_admin'    => 'boolean',
        ];
    }

    public function tenant() { return $this->belongsTo(Tenant::class); }

    public function isSuperAdmin(): bool { return (bool) $this->is_super_admin; }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function blacklistedPhones()
    {
        return $this->hasMany(BlacklistedPhone::class, 'blacklisted_by');
    }

    public function getRoleDisplayNameAttribute(): string
    {
        return match($this->attributes['role'] ?? 'customer') {
            'admin'   => 'Admin',
            'manager' => 'Manager',
            'support' => 'Support',
            default   => 'Customer',
        };
    }

    public function isAdmin(): bool
    {
        return ($this->attributes['role'] ?? null) === 'admin';
    }

    public function isBanned(): bool
    {
        return (bool) $this->is_banned;
    }

    public function hasRole(string $roleName): bool
    {
        return ($this->attributes['role'] ?? null) === $roleName;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->roleModel?->permissions ?? []);
    }
}
