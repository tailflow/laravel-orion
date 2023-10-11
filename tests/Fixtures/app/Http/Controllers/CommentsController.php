<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Models\Comment;

class CommentsController extends Controller
{
    public function model(): string
    {
        return Comment::class;
    }

    public function includes(): array
    {
        return [
            'commentable',
            'commentable.image'
        ];
    }
}
