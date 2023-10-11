<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\Post;
use Symfony\Component\HttpFoundation\Response;

class PostsController extends Controller
{
    public function model(): string
    {
        return Post::class;
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

    /**
     * @param Request $request
     * @param Post $entity
     * @param array $attributes
     * @return Response|null
     */
    protected function beforeSave(Request $request, Model $entity, array &$attributes): ?Response
    {
        if ($user = $request->user()) {
            $entity->user()->associate($user);
        }

        return null;
    }
}
