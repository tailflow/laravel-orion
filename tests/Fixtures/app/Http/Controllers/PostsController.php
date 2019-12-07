<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Models\Post;

class PostsController extends Controller
{
    /**
     * @var string|null $model
     */
    protected static $model = Post::class;
}
