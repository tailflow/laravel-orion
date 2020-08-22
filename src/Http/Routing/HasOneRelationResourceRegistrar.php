<?php

namespace Orion\Http\Routing;

class HasOneRelationResourceRegistrar extends RelationResourceRegistrar
{
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = ['batchStore', 'batchUpdate', 'batchDestroy', 'batchRestore', 'store', 'show', 'update', 'destroy', 'restore'];
}