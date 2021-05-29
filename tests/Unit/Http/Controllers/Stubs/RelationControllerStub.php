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

    protected function includes(): array
    {
        return ['testRelation'];
    }

    public function alwaysIncludes(): array
    {
        return ['testAlwaysIncludedRelation'];
    }

    protected function bindComponents(): void
    {
    }
}
