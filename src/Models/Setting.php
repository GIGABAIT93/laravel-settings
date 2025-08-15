<?php

namespace Gigabait93\LaravelSettings\Models;

use Gigabait93\LaravelSettings\Casts\SettingValueCast;
use Gigabait93\LaravelSettings\Services\SettingsService;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    public $timestamps = false;

    protected $fillable = ['key','value'];

    protected $casts = [
        'value' => SettingValueCast::class,
    ];

    protected static function booted(): void
    {
        static::saved(fn ($m) => app(SettingsService::class)->clearAllCache());
        static::deleted(fn ($m) => app(SettingsService::class)->clearAllCache());
    }

    public static function getByKey(string $key)
    {
        $row = self::where('key', $key)->first();
        return $row?->value;
    }

    public static function setByKey(string $key, $value): bool
    {
        return (bool) self::query()->updateOrCreate(['key'=>$key], ['value'=>$value]);
    }

    public static function deleteByKey(string $key): bool
    {
        return self::where('key',$key)->delete() > 0;
    }
}

