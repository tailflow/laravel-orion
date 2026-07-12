<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\Post;

class PostsBeforeFilterAppliedController extends Controller
{
    /**
     * @var string|null $model
     */
    protected $model = Post::class;

    public function filterableBy(): array
    {
        return ['title', 'body'];
    }

    /**
     * Remaps the client-facing "body" filter onto the "title" column to
     * exercise filter rewriting inside buildIndexFetchQuery.
     *
     * @param Request $request
     * @param array $filterDescriptor
     * @return array
     */
    protected function beforeFilterApplied(Request $request, array $filterDescriptor): array
    {
        if (($filterDescriptor['field'] ?? null) === 'body') {
            $filterDescriptor['field'] = 'title';
        }

        return $filterDescriptor;
    }
}
