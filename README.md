# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eden/slab-manager.svg?style=flat-square)](https://packagist.org/packages/eden/slab-manager)
[![Total Downloads](https://img.shields.io/packagist/dt/eden/slab-manager.svg?style=flat-square)](https://packagist.org/packages/eden/slab-manager)
![GitHub Actions](https://github.com/eden/slab-manager/actions/workflows/main.yml/badge.svg)

This package is used as a plugin style manager for a Laravel project.  This is originally based on
[Laravel-plugins](https://github.com/oneso/laravel-plugins).

## Installation

You can install the package via composer:

```bash
composer require eden/plugin-manager
```

Also add to providers the following:

```php
 Eden\SlabManager\PluginManagerServiceProvider::class,

```
## Usage

Plugins must be in the App/Plugins folder of your Laravel project.  Example structure of the plugin is as follows:

### Structure
Plugins must be in app/Plugins. Example plugin structure:
- Test
  - Http
    - Controllers
      - TestController.php
  - views
    - test.blade.php
  - migrations
    - 2018_06_15_000000_create_test_table.php
  - routes.php
  - TestPlugin.php

The TestPlugin class must extend the Eden\SlabManager\Slab class, containing a unique $name property and a boot() method.


### Views
In the boot() method of your plugin call `$this->enableViews()`.
Optional you can pass a relative path to the views directory, default to `views`.
Views automatically have a namespace (`"plugin:{name}"`), the name is defined by the the main plugin class in a camel case format, with `Plugin` stripped from the end. For the example above it would be `plugin:test`.

To render a view you can either write the namespace yourself or use the helper method `view()` in the plugin class. For example `view('plugin:test::some.view.name');`

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

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email schereja@gmail.com instead of using the issue tracker.

## Credits

-   [Jason Scherer](https://github.com/eden)
-   [All Contributors](../../contributors)

## License

The Apache License 2. Please see [License File](LICENSE.md) for more information.
