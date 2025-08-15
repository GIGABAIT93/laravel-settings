<?php

namespace Gigabait93\LaravelSettings\Providers;

use Gigabait93\LaravelSettings\Services\SettingsService;

class LaravelSettingsServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, function ($app) {
            return new SettingsService();
        });
    }

    public function boot(): void
    {
    }
}