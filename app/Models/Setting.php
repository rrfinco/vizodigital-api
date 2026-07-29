<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable((new static)->getTable())) {
                return $default;
            }

            $setting = static::query()->where('key', $key)->first();
        } catch (\Throwable) {
            return $default;
        }

        if (! $setting) {
            return $default;
        }

        $value = $setting->value;

        if (is_array($value) && array_key_exists('v', $value) && count($value) === 1) {
            return $value['v'];
        }

        return $value ?? $default;
    }

    public static function setValue(string $key, mixed $value, string $group = 'general'): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => ['v' => $value],
            ]
        );
    }
}
