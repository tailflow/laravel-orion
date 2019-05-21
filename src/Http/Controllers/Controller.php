<?php

namespace Laralord\Orion\Http\Controllers;

use Laralord\Orion\Traits\HandlesCRUDOperations;

class Controller extends BaseController
{
    use HandlesCRUDOperations;

    /**
     * Retrieves model related to resource.
     *
     * @return string
     */
    protected function getResourceModel()
    {
        return static::$model;
    }
}
