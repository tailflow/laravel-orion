<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Models\Post;

class BaseControllerStubWithWhitelistedFieldsAndRelations extends BaseController
{
    protected $model = Post::class;

    protected $request = PostRequest::class;

    protected function exposedScopes(): array
    {
        return ['testScope'];
    }

    protected function filterableBy(): array
    {
        return ['test_filterable_field'];
    }

    protected function sortableBy(): array
    {
        return ['test_sortable_field'];
    }

    protected function searchableBy(): array
    {
        return ['test_searchable_field'];
    }

    protected function includes(): array
    {
        return ['testRelation'];
    }

    protected function alwaysIncludes(): array
    {
        return ['testAlwaysIncludedRelation'];
    }

    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        return $this->getModel();
    }

    protected function bindComponents(): void
    {
    }
}
