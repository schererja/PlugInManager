<?php

namespace Eden\SlabManager;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Eden\SlabManager\Skeleton\SkeletonClass
 */
class SlabManagerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'slab-manager';
    }
}
