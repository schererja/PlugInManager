<?php

namespace Schererja\PlugInManager;

use Illuminate\Support\Facades\Facade;

class PluginManagerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'plugin-manager';
    }
}
