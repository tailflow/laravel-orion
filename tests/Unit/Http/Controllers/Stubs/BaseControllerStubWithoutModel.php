<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

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
}
