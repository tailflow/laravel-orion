<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\ValueObjects\Operations\MutatingOperationPayload;
use Orion\ValueObjects\Operations\Standard\StoreOperationPayload;

class PostsController extends Controller
{
    /**
     * @var string|null $model
     */
    protected $model = Post::class;

    /**
     * @param MutatingOperationPayload $payload
     * @return void
     */
    public function beforeSave(MutatingOperationPayload $payload)
    {
        if ($user = $payload->request->user()) {
            $payload->entity->user()->associate($user);
        }
    }

    public function sortableBy() : array
    {
        return ['title', 'user.name', 'meta->nested_field'];
    }

    public function filterableBy() : array
    {
        return ['title', 'position', 'publish_at', 'user.name', 'meta->nested_field'];
    }

    public function searchableBy() : array
    {
        return ['title', 'user.name'];
    }

    public function exposedScopes() : array
    {
        return ['published', 'publishedAt'];
    }

    /**
     * @return array
     */
    public function includes() : array
    {
        return ['user'];
    }
}
