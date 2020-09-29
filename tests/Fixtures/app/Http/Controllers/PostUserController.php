<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\Post;

class PostUserController extends RelationController
{
    /**
     * @var string|null $model
     */
    protected $model = Post::class;

    /**
     * @var string $relation
     */
    protected $relation = 'user';

    protected function includes(): array
    {
        return ['posts'];
    }
}