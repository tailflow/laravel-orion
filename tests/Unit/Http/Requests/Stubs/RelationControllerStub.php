<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Http\Requests\Stubs;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\Post;

class RelationControllerStub extends RelationController
{
    public function model(): string
    {
        return Post::class;
    }

    public function relation(): string
    {
        return 'tags';
    }
}
