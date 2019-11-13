<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Tag;

class HandlesStandardStoreOperationsTest extends TestCase
{
    /** @test */
    public function can_store_a_single_resource()
    {
        $payload = ['name' => 'my test resource'];

        $response = $this->post('/api/tags', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data']);
        $this->assertDatabaseHas('tags', $payload);
        $resource = Tag::query()->first();
        $response->assertJson(['data' => $resource->toArray()]);
    }
}
