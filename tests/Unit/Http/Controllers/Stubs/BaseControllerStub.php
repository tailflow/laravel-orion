<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Contracts\QueryBuilder;
use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleCollectionResource;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Repositories\PostBaseRepository;

class BaseControllerStub extends BaseController
{
    public function model(): string
    {
        return Post::class;
    }

    protected ?string $repository = PostBaseRepository::class;

    protected ?string $request = PostRequest::class;

    protected ?string $resource = SampleResource::class;

    protected ?string $collectionResource = SampleCollectionResource::class;

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
