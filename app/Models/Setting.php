<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    /**
     * Get a setting value by group and key.
     */
    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $setting = Cache::rememberForever("settings.{$group}.{$key}", function () use ($group, $key) {
            return static::where('group', $group)->where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $group, string $key, mixed $value, string $type = 'string'): static
    {
        $storedValue = match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        $setting = static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $storedValue, 'type' => $type]
        );

        Cache::forget("settings.{$group}.{$key}");
        Cache::forget("settings.group.{$group}");

        return $setting;
    }

    /**
     * Get all settings for a group.
     */
    public static function getGroup(string $group): array
    {
        return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            return static::where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Bulk set settings for a group.
     */
    public static function setGroup(string $group, array $settings, string $type = 'string'): void
    {
        foreach ($settings as $key => $value) {
            static::setValue($group, $key, $value, $type);
        }
    }
}
