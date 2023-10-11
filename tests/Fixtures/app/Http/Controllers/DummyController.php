<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Models\Post;

class DummyController extends Controller
{
    public function model(): string
    {
        return Post::class;
    }
}
