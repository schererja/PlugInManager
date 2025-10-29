<?php

namespace Schererja\PlugInManager;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class PluginManagerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        PluginManager::getInstance($this->app);

        $this->loadRoutesFrom(__DIR__.'/PluginManagerRoutes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('plugin-manager.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'plugin-manager');

        // Register the main class to use with the facade
        $this->app->singleton('plugin-manager', function ($app) {
            return PluginManager::getInstance($app);
        });
        $loader = AliasLoader::getInstance();

        $loader->alias('PlugInManager', 'Eden\PlugInManager\PluginManagerFacade::class');
    }
}
