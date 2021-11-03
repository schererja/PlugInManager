<?php

namespace Eden\PlugInManager;

use App\Http\Controllers\Controller;

class PluginManagerController extends Controller
{


    public function GetPlugins()
    {
        return PluginManager::getPlugins();
    }

    public function GetPluginById($id)
    {
        $plugins = PluginManager::getPlugins();


    }
}
