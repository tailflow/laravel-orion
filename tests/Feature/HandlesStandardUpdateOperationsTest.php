<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Tag;

class HandlesStandardUpdateOperationsTest extends TestCase
{
    /** @test */
    public function can_update_a_single_resource()
    {
        $originalResource = factory(Tag::class)->create();
        $payload = ['name' => 'test title', 'description' => 'test description'];

        $response = $this->patch("/api/tags/{$originalResource->id}", $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertDatabaseHas('tags', $payload);
        $updatedResource = Tag::query()->first();
        $response->assertJson(['data' => $updatedResource->toArray()]);
    }
}
