<?php

use Eden\PlugInManager\PluginManagerController;
use Illuminate\Support\Facades\Route;

Route::get('/api/v1/plugins', [PluginManagerController::class,'getPlugins']);
Route::get('/api/v1/plugins/{id}', [PluginManagerController::class,'getPluginById']);
Route::delete('/api/v1/plugins', function () {
    return "Deleted plugins";
});
Route::delete('/api/v1/plugins/{id}', function ($id) {
    return "Deleted plugin: ". $id;
});

Route::post('/api/v1/plugins', function (){
   return "Created a new plugin";
});
