<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrPolicySetting extends Model
{
    protected $fillable = ['key', 'value', 'value_type', 'description', 'is_active', 'updated_by'];

    protected $casts = ['is_active' => 'boolean'];

    public static function value(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->where('is_active', true)->first();
        if (! $setting) {
            return $default;
        }

        return match ($setting->value_type) {
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOL),
            'json' => json_decode($setting->value, true) ?? $default,
            default => $setting->value,
        };
    }
}
