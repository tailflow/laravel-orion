<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\ModelWithoutRelations;

class HandlesStandardUpdateOperationsTest extends TestCase
{
    /**
     * @test
     */
    public function can_update_a_single_resource()
    {
        $originalResource = factory(ModelWithoutRelations::class)->create();
        $payload = ['description' => 'test description'];

        $response = $this->patch("/api/model_without_relations/{$originalResource->id}", $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertDatabaseHas('model_without_relations', $payload);

        $updatedResource = ModelWithoutRelations::query()->first();
        $response->assertJson(['data' => $updatedResource->toArray()]);
    }
}
