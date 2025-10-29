# Plugin Manager for Laravel

A flexible and configurable plugin management system for Laravel applications. This package allows you to create modular plugins with their own views, routes, migrations, and controllers.

This package is based on [Laravel-plugins](https://github.com/oneso/laravel-plugins).

## Installation

You can install the package via composer:

```bash
composer require schererja/plugin-manager
```

The service provider will be automatically registered via Laravel's package auto-discovery.

You can optionally publish the configuration file:

```bash
php artisan vendor:publish --provider="Schererja\PlugInManager\PluginManagerServiceProvider" --tag="config"
```

## Configuration

After publishing the config file, you can customize the plugin manager behavior in `config/plugin-manager.php`:

```php
return [
    // Directory where plugins are located (relative to app base path)
    'plugin_directory' => env('PLUGIN_DIRECTORY', 'app/Plugins'),

    // Base namespace for plugins
    'plugin_namespace' => env('PLUGIN_NAMESPACE', 'App\\Plugins'),

    // Plugin class suffix (e.g., "Plugin" means TestPlugin for Test plugin)
    'plugin_class_suffix' => env('PLUGIN_CLASS_SUFFIX', 'Plugin'),

    // View namespace prefix (e.g., "plugin" means plugin:test::view)
    'view_namespace_prefix' => env('PLUGIN_VIEW_NAMESPACE_PREFIX', 'plugin'),

    // Storage directory for plugin cache files
    'storage_directory' => env('PLUGIN_STORAGE_DIRECTORY', 'plugins'),

    // Enable/disable plugin class mapping cache
    'cache_enabled' => env('PLUGIN_CACHE', true),

    // Error handling: 'exception', 'log', or 'silent'
    'error_mode' => env('PLUGIN_ERROR_MODE', 'exception'),

    // Auto-create plugin directory if it doesn't exist
    'auto_create_directory' => env('PLUGIN_AUTO_CREATE_DIRECTORY', true),
];
```

## Usage

### Basic Plugin Structure

By default, plugins should be placed in the `app/Plugins` directory. Example plugin structure:

```
app/Plugins/
├── Test/
│   ├── Http/
│   │   └── Controllers/
│   │       └── TestController.php
│   ├── views/
│   │   └── test.blade.php
│   ├── migrations/
│   │   └── 2018_06_15_000000_create_test_table.php
│   ├── routes.php
│   └── TestPlugin.php
```

### Plugin Class

Each plugin must have a main plugin class that extends `Schererja\PlugInManager\Plugin`:

```php
<?php

namespace App\Plugins\Test;

use Schererja\PlugInManager\Plugin;

class TestPlugin extends Plugin
{
    public string $name = 'test';
    public string $description = 'A test plugin';
    public string $version = '1.0.0';

    public function boot(): void
    {
        $this->enableViews();
        $this->enableRoutes();
        $this->enableMigrations();
    }
}
```

### Custom Configuration

You can customize the plugin system using environment variables:

```bash
# Custom plugin directory
PLUGIN_DIRECTORY=modules/Plugins

# Custom namespace
PLUGIN_NAMESPACE=Modules\\Plugins

# Custom class suffix
PLUGIN_CLASS_SUFFIX=Module

# Custom view namespace prefix
PLUGIN_VIEW_NAMESPACE_PREFIX=module

# Disable caching during development
PLUGIN_CACHE=false

# Log errors instead of throwing exceptions
PLUGIN_ERROR_MODE=log
```

### Views

In the boot() method of your plugin call `$this->enableViews()`.
Optional you can pass a relative path to the views directory, default to `views`.
Views automatically have a namespace that follows the pattern `"{prefix}:{name}"`, where the prefix is configurable (default: "plugin") and the name is derived from the plugin class name with the suffix removed.

To render a view you can either write the namespace yourself or use the helper method `view()` in the plugin class.

### Routes

In the boot() method of your plugin call `$this->enableRoutes()`.
Optional you can pass a relative path to the routes file, default to `routes.php`.
You automatically have access to the `$app` variable.
Routes are automatically grouped to your plugin namespace, so you only have to type the controller name without the namespace.

### Controllers

Controllers must be in PluginDirectory->Http->Controllers.

### Migrations

In the boot() method of your plugin call `$this->enableMigrations()`.
Optional you can pass a relative path to the migrations directory, default to `migrations`.
Keep in mind that migrations must follow the `yyyy_mm_dd_tttt_<name>.php` naming convention, for example `2014_10_12_000000_create_users_table.php` would be a valid migration.

### How to extend another plugin

see examples/extend

### Testing

```bash
composer test
```

### Code Quality

This project uses Laravel Pint for code style fixing and PHPStan for static analysis.

```bash
# Fix code style issues
composer pint

# Check code style without fixing
composer pint-test

# Run static analysis
composer stan

# Run all checks
composer check
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email [schereja@gmail.com](mailto:schereja@gmail.com) instead of using the issue tracker.

## Credits

- [Jason Scherer](https://github.com/schererja)
- [All Contributors](../../contributors)

## License

The Apache License 2. Please see [License File](LICENSE.md) for more information.
