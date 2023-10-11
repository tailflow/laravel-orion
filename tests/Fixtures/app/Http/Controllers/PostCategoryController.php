<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\Post;

class PostCategoryController extends RelationController
{
    public function model(): string
    {
        return Post::class;
    }

    public function relation(): string
    {
        return 'category';
    }

    public function includes(): array
    {
        return ['posts'];
    }
}
