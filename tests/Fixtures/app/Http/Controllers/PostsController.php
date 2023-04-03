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

    public function sortableBy(): array
    {
        return ['title', 'user.name', 'user.email', 'meta->nested_field'];
    }

    public function filterableBy(): array
    {
        return [
            'title',
            'position',
            'publish_at',
            'user.name',
            'meta.name',
            'meta.title',
            'meta->nested_field',
            'options',
            'options->nested_field',
        ];
    }

    public function searchableBy(): array
    {
        return ['title', 'meta.title', 'meta.name', 'user.name'];
    }

    public function exposedScopes(): array
    {
        return ['published', 'publishedAt'];
    }

    /**
     * @return array
     */
    public function includes(): array
    {
        return ['user', 'user.roles', 'image.*'];
    }
}
