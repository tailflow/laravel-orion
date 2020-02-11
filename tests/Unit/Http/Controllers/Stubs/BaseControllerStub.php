<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Http\Requests\TagRequest;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Tag;

class BaseControllerStub extends BaseController
{
    protected static $model = Tag::class;

    protected static $request = TagRequest::class;

    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        return Post::class;
    }
}
