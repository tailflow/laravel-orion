<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Orion\Http\Controllers\RelationController;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\User;

class UserPostsController extends RelationController
{
    protected $model = User::class;

    protected $relation = 'posts';

    public function includes(): array
    {
        return ['user'];
    }

    public function filterableBy(): array
    {
        return ['title', 'body'];
    }

    /**
     * Remaps the client-facing "body" filter onto the "title" column to
     * exercise filter rewriting inside buildIndexFetchQuery.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $filterDescriptor
     * @return array
     */
    protected function beforeFilterApplied(Request $request, Model $parentEntity, array $filterDescriptor): array
    {
        if (($filterDescriptor['field'] ?? null) === 'body') {
            $filterDescriptor['field'] = 'title';
        }

        return $filterDescriptor;
    }
}
