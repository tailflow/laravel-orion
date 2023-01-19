<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\Post;

class PostTagsController extends RelationController
{
    protected $model = Post::class;
    protected $relation = 'tags';

    public function sortableBy(): array
    {
        return ['name'];
    }
}
