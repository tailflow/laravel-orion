<?php


namespace Orion\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static list($perPage = 10): LengthAwarePaginator;
 * @method static setModel(string $model)
 * @method static builder()
 * @method static getById1(array $params): Model
 *
 */
class QueryBuilder extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'QueryBuilder';
    }
}
