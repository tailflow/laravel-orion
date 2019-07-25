<?php


namespace Orion\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static create($input, $model, $job)
 * @method static list($input, $model)
 * @method static builder()
 * @method static search($input, $model)
 * @method static show($input, $model)
 * @method static update($input, $model)
 * @method static destroy($input, $model)
 */
class JobResolver extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'JobResolver';
    }

}
