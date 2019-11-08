<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\ModelWithoutRelations;

class HandlesStandardShowOperationsTest extends TestCase
{
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
}
