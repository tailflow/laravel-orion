<?php

namespace Orion\Http\Controllers;

use Orion\Concerns\HandlesStandardOperations;

class Controller extends BaseController
{
    use HandlesStandardOperations;

    /**
     * Retrieves model related to resource.
     *
     * @return string
     */
    protected function resolveResourceModelClass(): string
    {
        return static::$model;
    }
}
