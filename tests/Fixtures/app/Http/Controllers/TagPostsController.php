<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\Tag;

class TagPostsController extends RelationController
{
    protected $model = Tag::class;
    protected $relation = 'posts';

    public function sortableBy(): array
    {
        return ['name'];
    }
}
