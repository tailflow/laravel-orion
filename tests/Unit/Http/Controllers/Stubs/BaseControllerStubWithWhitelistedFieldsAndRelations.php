<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Contracts\QueryBuilder;
use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Models\Post;

class BaseControllerStubWithWhitelistedFieldsAndRelations extends BaseController
{
    public function model(): string
    {
        return Post::class;
    }

    protected ?string $request = PostRequest::class;

    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        return $this->model();
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

    public function appends(): array
    {
        return ['testAppends'];
    }

    public function alwaysAppends(): array
    {
        return ['testAlwaysAppends'];
    }

    protected function bindComponents(): void
    {
    }

    public function getResourceQueryBuilder(): QueryBuilder
    {
        return $this->getQueryBuilder();
    }
}
