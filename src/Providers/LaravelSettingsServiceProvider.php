<?php

namespace Gigabait93\LaravelSettings\Providers;

use Illuminate\Support\ServiceProvider;
use Gigabait93\LaravelSettings\Services\SettingsService;

class LaravelSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, fn ($app) => new SettingsService());
        $this->mergeConfigFrom(__DIR__ . '/../../config/settings.php', 'settings');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/settings.php' => config_path('settings.php'),
            ], 'config');

            if (! $this->migrationPublished('create_settings_table.php')) {
                $timestamp = date('Y_m_d_His');
                $this->publishes([
                    __DIR__ . '/../../database/migrations/create_settings_table.php.stub'
                    => database_path("migrations/{$timestamp}_create_settings_table.php"),
                ], 'migrations');
            }

            $this->publishes([
                __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            ], 'laravel-settings-migrations');
        }
    }

    protected function migrationPublished(string $file): bool
    {
        return !empty(glob(database_path("migrations/*_{$file}")));
    }
}
