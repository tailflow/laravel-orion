<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Http\Controllers;

use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Unit\Http\Controllers\Stubs\ControllerStub;
use Orion\Tests\Unit\TestCase;

class ControllerTest extends TestCase
{
    /** @test */
    public function resolving_resource_model_class(): void
    {
        $stub = new ControllerStub();

        $this->assertEquals(Post::class, $stub->resolveResourceModelClass());
    }
}
