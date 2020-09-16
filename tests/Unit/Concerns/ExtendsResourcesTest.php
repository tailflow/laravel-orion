<?php

namespace Orion\Tests\Unit\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Orion\Concerns\ExtendsResources;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\TestCase;

class ExtendsResourcesTest extends TestCase
{
    /** @test */
    public function merging_data_with_array_representation_of_resource()
    {
        $stub = new ExtendsResourcesStub(new Post(['title' => 'test']));
        $this->assertSame([
            'title' => 'test',
            'additional-value' => 'test'
        ], $stub->toArrayWithMerge(new Request(), [
            'additional-value' => 'test'
        ]));
    }
}

class ExtendsResourcesStub extends JsonResource
{
    use ExtendsResources;
}
