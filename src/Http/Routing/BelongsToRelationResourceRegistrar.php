<?php

declare(strict_types=1);

namespace Orion\Http\Routing;

class BelongsToRelationResourceRegistrar extends RelationResourceRegistrar
{
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = [
        'batchUpdate',
        'batchDestroy',
        'batchRestore',
        'show',
        'update',
        'destroy',
        'restore',
    ];
}
