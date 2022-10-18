<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Contracts\QueryBuilder;
use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Models\Post;

class BaseControllerStubWithWhitelistedFieldsAndRelations extends BaseController
{
    protected $model = Post::class;

    protected $request = PostRequest::class;

    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        return $this->getModel();
    }

    public function exposedScopes(): array
    {
        return ['testScope'];
    }

    public function filterableBy(): array
    {
        return ['test_filterable_field'];
    }

    public function sortableBy(): array
    {
        return ['test_sortable_field'];
    }

    public function searchableBy(): array
    {
        return ['test_searchable_field'];
    }

    public function includes(): array
    {
        return ['testRelation'];
    }

    public function aggregates(): array
    {
        return ['test_aggregatable_field'];
    }

    public function alwaysIncludes(): array
    {
        return ['testAlwaysIncludedRelation'];
    }

    protected function bindComponents(): void
    {
    }

    public function getResourceQueryBuilder(): QueryBuilder
    {
        return $this->getQueryBuilder();
    }
}
