<?php

namespace Gigabait93\LaravelSettings\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class SettingValueCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) return $decoded;

        if ($value === 'true') return true;
        if ($value === 'false') return false;

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }
        return $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_array($value) || is_object($value)) return json_encode($value);
        return (string)$value;
    }
}
