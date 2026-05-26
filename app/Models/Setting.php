<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type'];

    protected const CACHE_KEY = 'app_settings_v1';

    public static function get(string $key, $default = null)
    {
        return self::all()->get($key, $default);
    }

    public static function set(string $key, $value, string $group = 'general', string $type = 'text'): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'type' => $type]
        );
        self::flushCache();
        return $setting;
    }

    public static function group(string $group): array
    {
        return self::where('group', $group)->pluck('value', 'key')->toArray();
    }

    public static function all($columns = ['*']): \Illuminate\Support\Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return parent::query()->pluck('value', 'key');
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }
}
