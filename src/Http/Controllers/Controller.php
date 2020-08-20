<?php

namespace Orion\Http\Controllers;

use Orion\Concerns\HandlesStandardBatchOperations;
use Orion\Concerns\HandlesStandardOperations;

abstract class Controller extends BaseController
{
    use HandlesStandardOperations, HandlesStandardBatchOperations;

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
