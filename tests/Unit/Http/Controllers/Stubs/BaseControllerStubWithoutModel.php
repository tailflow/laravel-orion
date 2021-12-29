<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Contracts\QueryBuilder;
use Orion\Http\Controllers\BaseController;

class BaseControllerStubWithoutModel extends BaseController
{
    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        // TODO: Implement resolveResourceModelClass() method.
    }

    public function getPrimaryQueryBuilder(): QueryBuilder
    {
        // TODO: Implement getPrimaryQueryBuilder() method.
    }
}
