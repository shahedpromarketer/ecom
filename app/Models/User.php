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

    /** Bangladeshi mobile numbers kept one way: 01XXXXXXXXX (no spaces, dashes or +880). */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            $digits = '0' . substr($digits, 3);
        }

        return $digits !== '' ? $digits : null;
    }

    /** The account for an email address or a mobile number (only when exactly one account has it). */
    public static function findForLogin(string $login): ?self
    {
        $login = trim($login);
        if (str_contains($login, '@')) {
            return static::where('email', $login)->first();
        }
        $phone = static::normalizePhone($login);
        if (! $phone || strlen($phone) < 6) {
            return null;
        }
        $found = static::where('phone', $phone)->limit(2)->get();

        return $found->count() === 1 ? $found->first() : null;
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = static::normalizePhone($value);
    }

    /** Email, or the mobile number for staff added without one. */
    public function loginId(): string
    {
        return $this->email ?: (string) $this->phone;
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
