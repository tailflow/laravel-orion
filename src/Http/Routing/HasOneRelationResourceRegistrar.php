<?php

declare(strict_types=1);

namespace Orion\Http\Routing;

class HasOneRelationResourceRegistrar extends RelationResourceRegistrar
{
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = [
        'store',
        'show',
        'update',
        'destroy',
        'restore',
    ];
}
