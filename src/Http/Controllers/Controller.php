<?php

namespace Orion\Http\Controllers;

use Orion\Concerns\HandlesStandardBatchOperations;
use Orion\Concerns\HandlesStandardOperations;
use Orion\Contracts\QueryBuilder;

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

    /**
     * Retrieves the query builder used to query the end-resource.
     *
     * @return QueryBuilder
     */
    public function getResourceQueryBuilder(): QueryBuilder
    {
        return $this->getQueryBuilder();
    }
}
