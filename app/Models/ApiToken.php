<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    protected $fillable = [
        'name', 'token', 'abilities', 'last_used_at', 'expires_at', 'is_active', 'created_by',
    ];

    protected $casts = [
        'abilities'    => 'array',
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public string $plainToken = '';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generate(string $name, array $abilities = [], ?Carbon $expiresAt = null): self
    {
        $plain  = bin2hex(random_bytes(32));
        $hashed = hash('sha256', $plain);

        $token = static::create([
            'name'       => $name,
            'token'      => $hashed,
            'abilities'  => $abilities,
            'expires_at' => $expiresAt,
            'created_by' => auth()->id(),
        ]);

        $token->plainToken = $plain;

        return $token;
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasAbility(string $ability): bool
    {
        if (empty($this->abilities)) {
            return true;
        }

        return in_array($ability, $this->abilities, true)
            || in_array('*', $this->abilities, true);
    }
}
