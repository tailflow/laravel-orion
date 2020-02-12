<?php

namespace Orion\Http\Controllers;

use Orion\Concerns\HandlesStandardOperations;

abstract class Controller extends BaseController
{
    use HandlesStandardOperations;

    /**
     * Retrieves model related to resource.
     *
     * @return string
     */
    public function resolveResourceModelClass(): string
    {
        return $this->getModel();
    }
}
