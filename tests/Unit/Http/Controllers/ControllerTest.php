<?php

namespace Orion\Tests\Unit\Http\Controllers;

use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Unit\Http\Controllers\Stubs\ControllerStub;
use Orion\Tests\Unit\TestCase;

class ControllerTest extends TestCase
{
    /** @test */
    public function resolving_resource_model_class()
    {
        $stub = new ControllerStub();

        $this->assertEquals(Post::class, $stub->resolveResourceModelClass());
    }
}
