<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Contracts\QueryBuilder;
use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Models\Post;

class BaseControllerStubWithoutComponents extends BaseController
{
    public function model(): string
    {
        return Post::class;
    }

    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        return $this->model();
    }

    public function getResourceQueryBuilder(): QueryBuilder
    {
        return $this->getQueryBuilder();
    }
}
