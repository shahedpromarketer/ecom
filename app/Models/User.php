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
        'tenant_id', 'is_super_admin', 'receives_orders',
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
            'receives_orders'   => 'boolean',
        ];
    }

    public function tenant() { return $this->belongsTo(Tenant::class); }

    /** Orders this person is responsible for (see OrderDistributor). */
    public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'assigned_to');
    }

    public function isSuperAdmin(): bool { return (bool) $this->is_super_admin; }

    /**
     * Staff of the current shop only. Users are not globally scoped (auth itself
     * loads users), so use this wherever a list of users is shown.
     */
    public function scopeInCurrentTenant($query)
    {
        if (\App\Support\Tenancy::active()) {
            $query->where('tenant_id', \App\Support\Tenancy::id());
        }

        return $query;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
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
        if (($this->attributes['role'] ?? null) === 'admin') {
            return 'Admin';
        }

        return $this->roleModel?->display_name ?? 'Staff';
    }

    /** Can sign in to the shop panel (owner/admin or staff with a role). */
    public function canUseAdminPanel(): bool
    {
        return $this->is_super_admin
            || ($this->attributes['role'] ?? null) === 'admin'
            || (($this->attributes['role'] ?? null) === 'staff' && $this->role_id);
    }

    /** Shop owners/admins and super admins skip permission checks. */
    public function hasFullAccess(): bool
    {
        return $this->is_super_admin || ($this->attributes['role'] ?? null) === 'admin';
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
        if ($this->hasFullAccess()) {
            return true;
        }

        return in_array($permission, $this->roleModel?->permissions ?? [], true);
    }
}
