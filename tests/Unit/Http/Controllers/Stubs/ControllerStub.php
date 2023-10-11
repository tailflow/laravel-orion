<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Models\Post;

class ControllerStub extends Controller
{
    public function model(): string
    {
        return Post::class;
    }
}
