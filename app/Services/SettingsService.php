<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->allCached();
        return $settings->get($key, $default);
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::setValue($key, $value, $group);
        $this->cache()->forget('settings_all');
    }

    public function getGroup(string $group): Collection
    {
        return Setting::where('group', $group)->get()->pluck('value', 'key');
    }

    private function allCached(): Collection
    {
        return $this->cache()->remember('settings_all', 3600, function () {
            return Setting::all()->pluck('value', 'key');
        });
    }

    private function cache()
    {
        return Cache::store('file');
    }
}
