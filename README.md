# Laravel Settings

A small package for saving and cache settings in Laravel.

## Installation

1. Set the package:

   ```bash
   composer require gigabait93/laravel-settings
   ```

2. Publish Config and Migration (optional):

   ```bash
   php artisan vendor:publish --provider="Gigabait93\LaravelSettings\Providers\LaravelSettingsServiceProvider" --tag=config
   php artisan vendor:publish --provider="Gigabait93\LaravelSettings\Providers\LaravelSettingsServiceProvider" --tag=migrations
   ```

3. Complete the migration:

   ```bash
   php artisan migrate
   ```

## Використання

Obtaining and setting values:

```php
use Gigabait93\LaravelSettings\Facades\Settings;

Settings::set('site.name', 'My Site');
$name = Settings::get('site.name');

// or helper
sset('items_per_page', 20);
$value = sget('items_per_page');
```

Managing a group of values:

```php
settings()->setMany([
    'a' => 1,
    'b' => true,
]);

$values = settings()->many(['a', 'b']); // ['a' => 1, 'b' => true]
```

## Cache

The package uses cache to reduce the number of references to the database. Cash setup can be changed in the file `config/settings.php`:

- `ttl` — Storage time in seconds (0 turns off cache).
- `prefix` — Key prefix.
- other parameters describe keys and tags.

## License

MIT