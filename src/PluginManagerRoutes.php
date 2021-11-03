<?php

use Eden\PlugInManager\PluginManagerController;
use Illuminate\Support\Facades\Route;

Route::get('/api/v1/plugins', [PluginManagerController::class, 'getPlugins']);
