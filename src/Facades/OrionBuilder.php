<?php


namespace Orion\Facades;

use Illuminate\Support\Facades\Facade;

/**
 *
 * @method static build($manager = '')
 *
 */
class OrionBuilder extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'OrionBuilder';
    }
}
