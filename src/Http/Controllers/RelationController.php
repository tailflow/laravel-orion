<?php

namespace Laralord\Orion\Http\Controllers;

use Laralord\Orion\Traits\HandlesRelationOperations;

class RelationController extends BaseController
{
    use HandlesRelationOperations;

    /**
     * @var string|null $relation
     */
    protected static $relation = null;

    /**
     * The list of pivot fields that can be set upon relation resource creation or update.
     *
     * @var bool
     */
    protected $pivotFillable = [];
}