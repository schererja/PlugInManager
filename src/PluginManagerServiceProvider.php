<?php

namespace Eden\PlugInManager;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class PluginManagerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot() : void
    {
        PluginManager::getInstance($this->app);
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'slab-manager');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'slab-manager');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/PluginManagerRoutes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('plugin-manager.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/slab-manager'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/slab-manager'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/slab-manager'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }

    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'plugin-manager');

        // Register the main class to use with the facade
        $this->app->singleton('plugin-manager', function ($app) {
            return PluginManager::getInstance($app);
        });
        $loader = AliasLoader::getInstance();

        $loader->alias('PlugInManager', 'Eden\PlugInManager\PluginManagerFacade::class');
    }
}
