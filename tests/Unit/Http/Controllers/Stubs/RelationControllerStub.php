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

    protected function bindComponents(): void
    {
    }
}
