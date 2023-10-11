<?php

declare(strict_types=1);

namespace Orion\Facades;

use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PendingResourceRegistration resource(string $name, string $controller, array $options = [])
 * @method static PendingResourceRegistration hasOneResource(string $resource, string $relation, string $controller, array $options = [])
 * @method static PendingResourceRegistration belongsToResource(string $resource, string $relation, string $controller, array $options = [])
 * @method static PendingResourceRegistration hasManyResource(string $resource, string $relation, string $controller, array $options = [])
 * @method static PendingResourceRegistration belongsToManyResource(string $resource, string $relation, string $controller, array $options = [])
 * @method static PendingResourceRegistration hasOneThroughResource(string $resource, string $relation, string $controller, array $options = [])
 * @method static PendingResourceRegistration hasManyThroughResource(string $resource, string $relation, string $controller, array $options = [])
 * @method static PendingResourceRegistration morphOneResource(string $resource, string $relation, string $controller, array $options = [])
 * @method static PendingResourceRegistration morphManyResource(string $resource, string $relation, string $controller, array $options = [])
 * @method static PendingResourceRegistration morphToResource(string $resource, string $relation, string $controller, array $options = [])
 * @method static PendingResourceRegistration morphToManyResource(string $resource, string $relation, string $controller, array $options = [])
 * @method static PendingResourceRegistration morphByManyResource(string $resource, string $relation, string $controller, array $options = [])
 *
 * @see \Orion\Orion
 */
class Orion extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'orion';
    }
}
