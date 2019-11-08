<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Collection;
use Orion\Tests\Fixtures\App\Models\ModelWithoutRelations;

class HandlesStandardOperationsTest extends TestCase
{
    /**
     * @test
     */
    public function can_get_a_list_of_resources()
    {
        /**
         * @var Collection $users
         */
        $resources = factory(ModelWithoutRelations::class)->times(5)->create();

        $response = $this->get('/api/model_without_relations');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links' => ['first', 'last', 'prev', 'next']]);
        $response->assertJson([
            'data' => $resources->map(function ($resource) {
                /**
                 * @var ModelWithoutRelations $resource
                 */
                return $resource->toArray();
            })->toArray()
        ]);
    }

    /**
     * @test
     */
    public function can_get_a_single_resource()
    {
        $resource = factory(ModelWithoutRelations::class)->create();

        $response = $this->get("/api/model_without_relations/{$resource->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => $resource->toArray()]);
    }

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

    /**
     * @test
     */
    public function can_delete_a_single_resource()
    {
        $resource = factory(ModelWithoutRelations::class)->create();

        $response = $this->delete("/api/model_without_relations/{$resource->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => $resource->toArray()]);
        $this->assertDatabaseMissing('users', ['id' => $resource->id]);
    }
}
