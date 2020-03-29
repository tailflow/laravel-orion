<?php

namespace Orion\Tests\Unit\Concerns;

use Illuminate\Http\Resources\Json\JsonResource;
use Orion\Concerns\ExtendsResources;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\TestCase;

class ExtendsResourcesTest extends TestCase
{
    /** @test */
    public function merging_data_with_array_representation_of_resource()
    {
        $stub = new ExtendsResourcesStub(new Tag(['name' => 'test']));
        $this->assertSame([
            'name' => 'test',
            'additional-value' => 'test'
        ], $stub->toArrayWithMerge(null, [
            'additional-value' => 'test'
        ]));
    }
}

class ExtendsResourcesStub extends JsonResource
{
    use ExtendsResources;
}
