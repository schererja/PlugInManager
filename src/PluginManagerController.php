<?php

namespace Schererja\PlugInManager;

use Illuminate\Routing\Controller;

class PluginManagerController extends Controller
{
    protected PluginManager $pluginManager;

    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function GetPlugins(): array
    {
        return $this->pluginManager->getPlugins();
    }

    public function GetPluginById(string $id): mixed
    {
        $plugins = $this->pluginManager->getPlugins();
        $pluginFound = null;
        foreach ($plugins as $plugin) {
            if ($plugin->id == $id) {
                $pluginFound = $plugin;
            }
        }

        return $pluginFound;
    }
}
