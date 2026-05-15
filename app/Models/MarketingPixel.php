<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingPixel extends Model
{
    protected $fillable = [
        'platform', 'pixel_id', 'api_secret', 'test_code',
        'head_code', 'body_code', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getByPlatform(string $platform): ?self
    {
        return static::where('platform', $platform)->first();
    }
}
