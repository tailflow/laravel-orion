<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Models\Tag;

class BaseControllerStubWithoutComponents extends BaseController
{
    /**
     * @var string $tag
     */
    protected $model = Tag::class;

    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        return $this->getModel();
    }
}
