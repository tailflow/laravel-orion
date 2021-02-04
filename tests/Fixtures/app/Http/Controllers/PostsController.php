<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\Post;

class PostsController extends Controller
{
    /**
     * @var string|null $model
     */
    protected $model = Post::class;

    /**
     * @param Request $request
     * @param Post $entity
     * @return mixed|void
     */
    protected function beforeSave(Request $request, $entity)
    {
        if ($user = $request->user()) {
            $entity->user()->associate($user);
        }
    }

    protected function sortableBy() : array
    {
        return ['title', 'user.name'];
    }

    protected function filterableBy() : array
    {
        return ['title', 'position', 'publish_at', 'user.name'];
    }

    protected function searchableBy() : array
    {
        return ['title', 'user.name'];
    }

    protected function exposedScopes() : array
    {
        return ['published', 'publishedAt'];
    }

    /**
     * @return array
     */
    protected function includes() : array
    {
        return ['user'];
    }
}
