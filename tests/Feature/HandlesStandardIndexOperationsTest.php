<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Collection;
use Orion\Tests\Fixtures\App\Models\ModelWithoutRelations;

class HandlesStandardIndexOperationsTest extends TestCase
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
}
