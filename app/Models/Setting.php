<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'autoload'];

    protected $casts = ['autoload' => 'boolean'];

    const CACHE_KEY = 'seconds_settings';

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = static::allCached();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::flushCache();
    }

    public static function allCached(): array
    {
        return Cache::rememberForever(static::CACHE_KEY, function () {
            return static::where('autoload', true)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }
}
