<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Models\Post;

class BaseControllerStubWithWhitelistedFieldsAndRelations extends BaseController
{
    protected $model = Post::class;

    protected $request = PostRequest::class;

    protected function exposedScopes()
    {
        return ['testScope'];
    }

    protected function filterableBy()
    {
        return ['test_filterable_field'];
    }

    protected function sortableBy()
    {
        return ['test_sortable_field'];
    }

    protected function searchableBy()
    {
        return ['test_searchable_field'];
    }

    protected function includes()
    {
        return ['testRelation'];
    }

    protected function alwaysIncludes()
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
        return;
    }
}
