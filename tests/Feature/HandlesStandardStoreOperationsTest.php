<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\ModelWithoutRelations;

class HandlesStandardStoreOperationsTest extends TestCase
{
    /**
     * @test
     */
    public function can_store_a_single_resource()
    {
        $payload = ['name' => 'my test resource'];

        $response = $this->post('/api/model_without_relations', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data']);
        $this->assertDatabaseHas('model_without_relations', $payload);
        $resource = ModelWithoutRelations::query()->first();
        $response->assertJson(['data' => $resource->toArray()]);
    }
}
