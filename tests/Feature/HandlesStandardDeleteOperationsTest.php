<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\ModelWithoutRelations;

class HandlesStandardDeleteOperationsTest extends TestCase
{
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
