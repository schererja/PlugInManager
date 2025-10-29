<?php

/*
 * Plugin Manager Configuration
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Plugin Directory
    |--------------------------------------------------------------------------
    |
    | This option controls the directory where plugins are located.
    | The path should be relative to the application's base path.
    |
    */
    'plugin_directory' => env('PLUGIN_DIRECTORY', 'app/Plugins'),

    /*
    |--------------------------------------------------------------------------
    | Plugin Base Namespace
    |--------------------------------------------------------------------------
    |
    | This option controls the base namespace for plugins.
    | Plugins will be loaded using this namespace pattern.
    |
    */
    'plugin_namespace' => env('PLUGIN_NAMESPACE', 'App\\Plugins'),

    /*
    |--------------------------------------------------------------------------
    | Plugin Class Suffix
    |--------------------------------------------------------------------------
    |
    | This option controls the suffix used for plugin main classes.
    | For example, with suffix "Plugin", a plugin named "Test" will
    | require a class named "TestPlugin".
    |
    */
    'plugin_class_suffix' => env('PLUGIN_CLASS_SUFFIX', 'Plugin'),

    /*
    |--------------------------------------------------------------------------
    | View Namespace Prefix
    |--------------------------------------------------------------------------
    |
    | This option controls the prefix used for plugin view namespaces.
    | For example, with prefix "plugin", views will be accessible as
    | "plugin:pluginname::viewname".
    |
    */
    'view_namespace_prefix' => env('PLUGIN_VIEW_NAMESPACE_PREFIX', 'plugin'),

    /*
    |--------------------------------------------------------------------------
    | Storage Directory
    |--------------------------------------------------------------------------
    |
    | This option controls the directory where plugin-related storage
    | files (like class maps) are stored.
    |
    */
    'storage_directory' => env('PLUGIN_STORAGE_DIRECTORY', 'plugins'),

    /*
    |--------------------------------------------------------------------------
    | Cache Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether plugin class mapping should be cached.
    | Caching improves performance but may need to be disabled during
    | development.
    |
    */
    'cache_enabled' => env('PLUGIN_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Error Handling Mode
    |--------------------------------------------------------------------------
    |
    | This option controls how plugin loading errors are handled.
    | Options: 'exception' (throw exceptions), 'log' (log errors), 'silent' (ignore errors)
    |
    */
    'error_mode' => env('PLUGIN_ERROR_MODE', 'exception'),

    /*
    |--------------------------------------------------------------------------
    | Auto-create Plugin Directory
    |--------------------------------------------------------------------------
    |
    | This option controls whether the plugin directory should be
    | automatically created if it doesn't exist.
    |
    */
    'auto_create_directory' => env('PLUGIN_AUTO_CREATE_DIRECTORY', true),
];
