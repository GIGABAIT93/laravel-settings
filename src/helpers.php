<?php

use Gigabait93\LaravelSettings\Services\SettingsService;

if (!function_exists('settings')) {
    function settings(): SettingsService
    {
        return app(SettingsService::class);
    }
}

if (!function_exists('sget')) {
    function sget(string $key, mixed $default = null): mixed
    {
        return settings()->get($key, $default);
    }
}

if (!function_exists('sset')) {
    function sset(string $key, mixed $value): void
    {
        settings()->set($key, $value);
    }
}
