<?php

namespace Eden\PlugInManager;

use function Composer\Autoload\includeFile;

class ClassLoader
{
    protected $pluginManager;

    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function loadClass($class)
    {
        if (isset($this->pluginManager->getClassMap()[$class])) {
            includeFile($this->pluginManager->getClassMap()[$class]);

            return true;
        }

    }

}
