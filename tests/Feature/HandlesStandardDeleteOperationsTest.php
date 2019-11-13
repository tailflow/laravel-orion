<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Tag;

class HandlesStandardDeleteOperationsTest extends TestCase
{
    /** @test */
    public function can_delete_a_single_resource()
    {
        $resource = factory(Tag::class)->create();

        $response = $this->delete("/api/tags/{$resource->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => $resource->toArray()]);
        $this->assertDatabaseMissing('tags', ['id' => $resource->id]);
    }
}
