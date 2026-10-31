<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One courier account of a shop (a shop may have several per courier, e.g. two Pathao stores).
 * Keys are stored encrypted. Each account has its own secret webhook address.
 */
class CourierSetting extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    /** courier => label and the fields its account form asks for. */
    public const COURIERS = [
        'steadfast' => ['label' => 'Steadfast', 'color' => 'bg-green-600', 'fields' => [
            'api_key'              => 'API Key',
            'api_secret'           => 'Secret Key',
            'extra.panel_email'    => 'Panel login email (optional — for customer success rate)',
            'extra.panel_password' => 'Panel password (optional — for customer success rate)',
        ]],
        'pathao' => ['label' => 'Pathao', 'color' => 'bg-red-500', 'fields' => [
            'api_key'        => 'Client ID',
            'api_secret'     => 'Client Secret',
            'extra.username' => 'Login email',
            'extra.password' => 'Login password',
            'extra.store_id' => 'Store ID',
        ]],
        'redx' => ['label' => 'RedX', 'color' => 'bg-red-700', 'fields' => [
            'api_key'               => 'API access token',
            'extra.pickup_store_id' => 'Pickup store ID (optional)',
        ]],
        'carrybee' => ['label' => 'CarryBee', 'color' => 'bg-amber-500', 'fields' => [
            'api_key'              => 'Client ID',
            'api_secret'           => 'Client Secret',
            'extra.client_context' => 'Client Context',
            'extra.store_id'       => 'Store ID',
        ]],
        'paperfly' => ['label' => 'Paperfly', 'color' => 'bg-yellow-500', 'fields' => [
            'extra.username'   => 'Merchant panel username',
            'extra.password'   => 'Merchant panel password',
            'api_key'          => 'paperflykey',
            'extra.store_name' => 'Store name',
        ]],
    ];

    /** Fields that are secrets: never shown back, kept when the form leaves them empty. */
    public const SECRET_FIELDS = ['api_key', 'api_secret', 'extra.password', 'extra.panel_password'];

    protected $fillable = [
        'courier', 'name', 'api_key', 'api_secret', 'base_url',
        'webhook_url', 'extra_config', 'is_active', 'is_default', 'sandbox', 'tenant_id',
    ];

    protected $hidden = ['api_key', 'api_secret', 'extra_config', 'webhook_token'];

    protected $casts = [
        'is_active'       => 'boolean',
        'is_default'      => 'boolean',
        'sandbox'         => 'boolean',
        'api_key'         => 'encrypted',
        'api_secret'      => 'encrypted',
        'extra_config'    => 'encrypted:array',
        'last_webhook_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $a) {
            $a->webhook_token ??= Str::random(40);
            $a->name ??= self::COURIERS[$a->courier]['label'] ?? ucfirst($a->courier);
        });
    }

    public function label(): string
    {
        $courier = self::COURIERS[$this->courier]['label'] ?? ucfirst($this->courier);

        return $this->name && $this->name !== $courier ? "{$courier} · {$this->name}" : $courier;
    }

    public function extra(string $key, $default = null)
    {
        return ($this->extra_config ?? [])[$key] ?? $default;
    }

    public function webhookUrl(): string
    {
        return rtrim(config('app.url'), '/') . '/api/courier-webhook/' . $this->webhook_token;
    }

    /** The account to use for a courier when staff don't pick one. */
    public static function defaultFor(string $courier): ?self
    {
        return static::where('courier', $courier === 'carrybe' ? 'carrybee' : $courier)->where('is_active', true)
            ->orderByDesc('is_default')->orderBy('id')->first();
    }

    /** Kept for older code paths: the default account of a courier. */
    public static function forCourier(string $courier, ?int $tenantId = null): ?self
    {
        $q = static::where('courier', $courier === 'carrybe' ? 'carrybee' : $courier);
        if ($tenantId !== null) {
            $q->where('tenant_id', $tenantId);
        }

        return $q->orderByDesc('is_active')->orderByDesc('is_default')->orderBy('id')->first();
    }
}
