<?php

namespace Gigabait93\LaravelSettings\Facades;

use Gigabait93\LaravelSettings\Services\SettingsService;
use Illuminate\Support\Facades\Facade;

class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingsService::class;
    }
}
