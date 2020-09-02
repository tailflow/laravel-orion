<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\Post;

class RelationControllerStub extends RelationController
{
    /**
     * @var string $model
     */
    protected $model = Post::class;

    /**
     * @var string $relation
     */
    protected $relation = 'user';

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

    protected function bindComponents(): void
    {
        return;
    }
}
