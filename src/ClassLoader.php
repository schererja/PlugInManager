<?php

namespace Schererja\PlugInManager;

class ClassLoader
{
    protected PluginManager $pluginManager;

    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function loadClass(string $class): bool
    {
        if (isset($this->pluginManager->getClassMap()[$class])) {
            require_once $this->pluginManager->getClassMap()[$class];

            return true;
        }

        return false;
    }
}
