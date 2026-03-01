<?php

declare(strict_types=1);

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::getAll()->firstWhere('key', $key);

        return $setting ? self::castValue($setting->value, $setting->type) : $default;
    }

    public static function setValue(string $key, mixed $value, string $group = 'general', string $type = 'string'): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) || is_bool($value) ? json_encode($value) : (string) $value,
                'group' => $group,
                'type' => $type,
            ]
        );
        self::clearCache();

        return $setting;
    }

    public static function getAll(): \Illuminate\Support\Collection
    {
        return Cache::remember('settings_all', 3600, fn () => self::orderBy('group')->orderBy('key')->get());
    }

    public static function clearCache(): void
    {
        Cache::forget('settings_all');
    }

    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'integer' => (int) $value,
            default => $value,
        };
    }
}
