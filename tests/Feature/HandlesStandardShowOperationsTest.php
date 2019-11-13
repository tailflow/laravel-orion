<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Tag;

class HandlesStandardShowOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_single_resource()
    {
        $resource = factory(Tag::class)->create();

        $response = $this->get("/api/tags/{$resource->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => $resource->toArray()]);
    }
}
