<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleCollectionResource;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;

class BaseControllerStub extends BaseController
{
    /**
     * @var string $tag
     */
    protected $model = Post::class;

    /**
     * @var string $request
     */
    protected $request = PostRequest::class;

    /**
     * @var string $resource
     */
    protected $resource = SampleResource::class;

    /**
     * @var string $collectionResource
     */
    protected $collectionResource = SampleCollectionResource::class;

    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        return $this->getModel();
    }
}
