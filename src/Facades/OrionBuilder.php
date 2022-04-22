<?php


namespace Orion\Facades;

use Illuminate\Support\Facades\Facade;
use Orion\Concerns\HandlesEloquentOperations;

/**
 *
 * @method static HandlesEloquentOperations build($manager = '')
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
