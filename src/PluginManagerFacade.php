<?php

namespace Eden\PlugInManager;

use Illuminate\Support\Facades\Facade;

/**
 */
class PluginManagerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'plugin-manager';
    }
}
