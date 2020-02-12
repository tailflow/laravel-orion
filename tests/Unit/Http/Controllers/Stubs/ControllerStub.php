<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Models\Post;

class ControllerStub extends Controller
{
    protected $model = Post::class;
}
