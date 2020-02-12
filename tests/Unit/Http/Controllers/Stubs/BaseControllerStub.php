<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Http\Requests\TagRequest;
use Orion\Tests\Fixtures\App\Http\Resources\TagCollectionResource;
use Orion\Tests\Fixtures\App\Http\Resources\TagResource;
use Orion\Tests\Fixtures\App\Models\Tag;

class BaseControllerStub extends BaseController
{
    /**
     * @var string $tag
     */
    protected $model = Tag::class;

    /**
     * @var string $request
     */
    protected $request = TagRequest::class;

    /**
     * @var string $resource
     */
    protected $resource = TagResource::class;

    /**
     * @var string $collectionResource
     */
    protected $collectionResource = TagCollectionResource::class;

    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        return $this->getModel();
    }
}
