<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\Post;

class PostsController extends Controller
{
    use DisableAuthorization;

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

    protected function sortableBy()
    {
        return ['title', 'user.name'];
    }

    protected function filterableBy()
    {
        return ['title', 'position', 'user.name'];
    }

    protected function searchableBy()
    {
        return ['title', 'user.name'];
    }

    protected function exposedScopes()
    {
        return ['published', 'publishedAt'];
    }

    /**
     * @return array
     */
    protected function includes()
    {
        return ['user'];
    }
}
