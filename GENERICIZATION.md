# Plugin Manager Genericization Summary

This document outlines the changes made to make the Plugin Manager more generic and configurable.

## Changes Made

### 1. Configuration System

- **Before**: No configuration options, everything hardcoded
- **After**: Comprehensive configuration system with the following options:
  - `plugin_directory`: Customizable plugin directory location
  - `plugin_namespace`: Configurable base namespace for plugins
  - `plugin_class_suffix`: Customizable suffix for plugin classes
  - `view_namespace_prefix`: Configurable prefix for view namespaces
  - `storage_directory`: Customizable storage directory for cache files
  - `cache_enabled`: Option to enable/disable caching
  - `error_mode`: Configurable error handling (exception, log, silent)
  - `auto_create_directory`: Option to auto-create plugin directory

### 2. Hardcoded Paths Removed

- **Before**: Plugin directory hardcoded to `app/Plugins`
- **After**: Configurable via `plugin_directory` setting

- **Before**: Plugin namespace hardcoded to `App\\Plugins\\`
- **After**: Configurable via `plugin_namespace` setting

- **Before**: Storage path hardcoded to `storage/plugins/`
- **After**: Configurable via `storage_directory` setting

### 3. Naming Conventions Made Flexible

- **Before**: Plugin class suffix hardcoded to "Plugin"
- **After**: Configurable via `plugin_class_suffix` setting

- **Before**: View namespace prefix hardcoded to "Plugin:"
- **After**: Configurable via `view_namespace_prefix` setting

### 4. Error Handling Improved

- **Before**: Used `dd()` which stops execution (development only)
- **After**: Configurable error handling:
  - `exception`: Throw exceptions (default)
  - `log`: Log errors and continue
  - `silent`: Ignore errors

### 5. Environment Variable Support

All configuration options can be overridden using environment variables:

```bash
PLUGIN_DIRECTORY=modules/Plugins
PLUGIN_NAMESPACE=Modules\\Plugins
PLUGIN_CLASS_SUFFIX=Module
PLUGIN_VIEW_NAMESPACE_PREFIX=module
PLUGIN_CACHE=false
PLUGIN_ERROR_MODE=log
```

### 6. Documentation Updated

- README updated to reflect new configurable nature
- Added configuration examples
- Fixed naming inconsistencies
- Added environment file example

## Benefits

1. **Framework Agnostic**: Can be adapted to different project structures
2. **Environment Specific**: Different configurations for dev/staging/production
3. **Backward Compatible**: Default values maintain existing behavior
4. **Production Ready**: Proper error handling for production environments
5. **Developer Friendly**: Extensive configuration options for different use cases

## Migration Guide

### For Existing Users

No changes required if you're using the default structure (`app/Plugins`). The package maintains backward compatibility.

### For Custom Configurations

1. Publish the config file: `php artisan vendor:publish --provider="Schererja\PlugInManager\PluginManagerServiceProvider" --tag="config"`
2. Modify `config/plugin-manager.php` or set environment variables
3. Update your plugin directory structure if needed

## Example Use Cases

### 1. Microservices Architecture

```bash
PLUGIN_DIRECTORY=services
PLUGIN_NAMESPACE=App\\Services
PLUGIN_CLASS_SUFFIX=Service
```

### 2. Module-based Application

```bash
PLUGIN_DIRECTORY=modules
PLUGIN_NAMESPACE=Modules
PLUGIN_CLASS_SUFFIX=Module
PLUGIN_VIEW_NAMESPACE_PREFIX=module
```

### 3. Development Environment

```bash
PLUGIN_CACHE=false
PLUGIN_ERROR_MODE=log
PLUGIN_AUTO_CREATE_DIRECTORY=true
```

### 4. Production Environment

```bash
PLUGIN_CACHE=true
PLUGIN_ERROR_MODE=silent
PLUGIN_AUTO_CREATE_DIRECTORY=false
```
