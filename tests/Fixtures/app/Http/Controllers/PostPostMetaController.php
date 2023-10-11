<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\Post;

class PostPostMetaController extends RelationController
{
    public function model(): string
    {
        return Post::class;
    }

    public function relation(): string
    {
        return 'meta';
    }

    public function includes(): array
    {
        return ['post'];
    }

    public function aggregates(): array
    {
        return ['post'];
    }
}
