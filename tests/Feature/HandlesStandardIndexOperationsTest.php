<?php

namespace Orion\Tests\Feature;

use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Support\Collection;
use Orion\Tests\Fixtures\App\Models\History;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\Team;

class HandlesStandardIndexOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_list_of_resources()
    {
        /**
         * @var Collection $resources
         */
        $resources = factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 5,
                'total' => 5
            ]
        ]);
        $response->assertJson([
            'data' => $resources->map(function ($resource) {
                /**
                 * @var Tag $resource
                 */
                return $resource->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_paginated_list_of_resources()
    {
        /**
         * @var Collection $resources
         */
        $resources = factory(Tag::class)->times(45)->create();

        $response = $this->get('/api/tags?page=2');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 2,
                'from' => 16,
                'last_page' => 3,
                'per_page' => 15,
                'to' => 30,
                'total' => 45
            ]
        ]);
        $response->assertJson([
            'data' => $resources->forPage(2, 15)->values()->map(function ($resource) {
                /**
                 * @var Tag $resource
                 */
                return $resource->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_limited_list_of_resources_with_a_valid_limit_query_parameter()
    {
        /**
         * @var Collection $resources
         */
        $resources = factory(Tag::class)->times(15)->create();

        $response = $this->get('/api/tags?limit=5');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 3,
                'per_page' => 5,
                'to' => 5,
                'total' => 15
            ]
        ]);
        $response->assertJson([
            'data' => $resources->take(5)->values()->map(function ($resource) {
                /**
                 * @var Tag $resource
                 */
                return $resource->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_with_limit_query_parameter_being_a_string()
    {
        /**
         * @var Collection $resources
         */
        $resources = factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags?limit=is+a+string');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 5,
                'total' => 5
            ]
        ]);
        $response->assertJson([
            'data' => $resources->map(function ($resource) {
                /**
                 * @var Tag $resource
                 */
                return $resource->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_with_limit_query_parameter_being_a_negative_number()
    {
        /**
         * @var Collection $resources
         */
        $resources = factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags?limit=-1');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 5,
                'total' => 5
            ]
        ]);
        $response->assertJson([
            'data' => $resources->map(function ($resource) {
                /**
                 * @var Tag $resource
                 */
                return $resource->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_with_limit_query_parameter_being_zero()
    {
        /**
         * @var Collection $resources
         */
        $resources = factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags?limit=0');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 5,
                'total' => 5
            ]
        ]);
        $response->assertJson([
            'data' => $resources->map(function ($resource) {
                /**
                 * @var Tag $resource
                 */
                return $resource->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_with_limit_query_parameter_missing_value()
    {
        /**
         * @var Collection $resources
         */
        $resources = factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags?limit=');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 5,
                'total' => 5
            ]
        ]);
        $response->assertJson([
            'data' => $resources->map(function ($resource) {
                /**
                 * @var Tag $resource
                 */
                return $resource->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_resources_with_a_valid_sort_query_parameter()
    {
        $resourceC = factory(Tag::class)->create(['name' => 'C'])->refresh();
        $resourceB = factory(Tag::class)->create(['name' => 'B'])->refresh();
        $resourceA = factory(Tag::class)->create(['name' => 'A'])->refresh();

        $response = $this->get('/api/tags?sort=name|asc');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 3,
                'total' => 3
            ]
        ]);

        $this->assertEquals($resourceA->toArray(), $response->json('data.0'));
        $this->assertEquals($resourceB->toArray(), $response->json('data.1'));
        $this->assertEquals($resourceC->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_resources_with_sort_query_parameter_missing_direction()
    {
        $resourceA = factory(Tag::class)->create(['name' => 'A'])->refresh();
        $resourceB = factory(Tag::class)->create(['name' => 'B'])->refresh();
        $resourceC = factory(Tag::class)->create(['name' => 'C'])->refresh();

        $response = $this->get('/api/tags?sort=name');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 3,
                'total' => 3
            ]
        ]);

        $this->assertEquals($resourceA->toArray(), $response->json('data.0'));
        $this->assertEquals($resourceB->toArray(), $response->json('data.1'));
        $this->assertEquals($resourceC->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_desc_sorted_resources_with_a_valid_sort_query_parameter()
    {
        $resourceA = factory(Tag::class)->create(['name' => 'A'])->refresh();
        $resourceB = factory(Tag::class)->create(['name' => 'B'])->refresh();
        $resourceC = factory(Tag::class)->create(['name' => 'C'])->refresh();

        $response = $this->get('/api/tags?sort=name|desc');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 3,
                'total' => 3
            ]
        ]);

        $this->assertEquals($resourceC->toArray(), $response->json('data.0'));
        $this->assertEquals($resourceB->toArray(), $response->json('data.1'));
        $this->assertEquals($resourceA->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function cannot_get_a_list_of_resources_sorted_by_not_whitelisted_field()
    {
        $resourceC = factory(Tag::class)->create(['description' => 'C'])->refresh();
        $resourceB = factory(Tag::class)->create(['description' => 'B'])->refresh();
        $resourceA = factory(Tag::class)->create(['description' => 'A'])->refresh();

        $response = $this->get('/api/tags?sort=description|asc');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 3,
                'total' => 3
            ]
        ]);
        $this->assertEquals($resourceC->toArray(), $response->json('data.0'));
        $this->assertEquals($resourceB->toArray(), $response->json('data.1'));
        $this->assertEquals($resourceA->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_resources_with_sort_query_parameter_mising_value()
    {
        $resourceA = factory(Tag::class)->create(['name' => 'A'])->refresh();
        $resourceB = factory(Tag::class)->create(['name' => 'B'])->refresh();
        $resourceC = factory(Tag::class)->create(['name' => 'C'])->refresh();

        $response = $this->get('/api/tags?sort=');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 3,
                'total' => 3
            ]
        ]);

        $this->assertEquals($resourceA->toArray(), $response->json('data.0'));
        $this->assertEquals($resourceB->toArray(), $response->json('data.1'));
        $this->assertEquals($resourceC->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_by_has_one_relation_field_resources()
    {
        /**
         * @var Tag $resourceA
         * @var Tag $resourceB
         * @var Tag $resourceC
         */
        $resourceA = factory(Tag::class)->create()->refresh();
        $resourceA->meta()->save(factory(TagMeta::class)->make(['key' => 'A']));
        $resourceB = factory(Tag::class)->create()->refresh();
        $resourceB->meta()->save(factory(TagMeta::class)->make(['key' => 'B']));
        $resourceC = factory(Tag::class)->create()->refresh();
        $resourceC->meta()->save(factory(TagMeta::class)->make(['key' => 'C']));

        $response = $this->get('/api/tags?sort=meta~key|desc');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 3,
                'total' => 3
            ]
        ]);

        $this->assertEquals($resourceC->toArray(), $response->json('data.0'));
        $this->assertEquals($resourceB->toArray(), $response->json('data.1'));
        $this->assertEquals($resourceA->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_by_belongs_to_relation_field_resources()
    {
        /**
         * @var Tag $resourceA
         * @var Tag $resourceB
         * @var Tag $resourceC
         */
        $resourceA = factory(Tag::class)->create(['team_id' => factory(Team::class)->create(['name' => 'A'])->id])->refresh();
        $resourceB = factory(Tag::class)->create(['team_id' => factory(Team::class)->create(['name' => 'B'])->id])->refresh();
        $resourceC = factory(Tag::class)->create(['team_id' => factory(Team::class)->create(['name' => 'C'])->id])->refresh();

        $response = $this->get('/api/tags?sort=team~name|desc');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 3,
                'total' => 3
            ]
        ]);

        $this->assertEquals($resourceC->toArray(), $response->json('data.0'));
        $this->assertEquals($resourceB->toArray(), $response->json('data.1'));
        $this->assertEquals($resourceA->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_by_has_one_through_relation_field_resources()
    {
        /**
         * @var Team $teamA
         * @var Team $teamB
         * @var Team $teamC
         */
        $teamA = factory(Team::class)->create()->refresh();
        $supplierA = factory(Supplier::class)->create(['team_id' => $teamA->id]);
        factory(History::class)->create(['code' => 'A', 'supplier_id' => $supplierA->id]);
        $teamB = factory(Team::class)->create()->refresh();
        $supplierB = factory(Supplier::class)->create(['team_id' => $teamA->id]);
        factory(History::class)->create(['code' => 'B', 'supplier_id' => $supplierB->id]);
        $teamC = factory(Team::class)->create()->refresh();
        $supplierC = factory(Supplier::class)->create(['team_id' => $teamA->id]);
        factory(History::class)->create(['code' => 'C', 'supplier_id' => $supplierC->id]);

        $this->withoutExceptionHandling();

        $response = $this->get('/api/teams?sort=supplierHistory~code|desc');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 3,
                'total' => 3
            ]
        ]);

        $this->assertEquals($teamC->toArray(), $response->json('data.0'));
        $this->assertEquals($teamB->toArray(), $response->json('data.1'));
        $this->assertEquals($teamA->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources()
    {
        /**
         * @var Tag $matchingResource
         */
        $matchingResource = factory(Tag::class)->create(['name' => 'match'])->refresh();
        factory(Tag::class)->create(['name' => 'not match'])->refresh();

        $response = $this->get('/api/tags?name=match');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 1,
                'total' => 1
            ]
        ]);

        $response->assertJson([
           'data' => [$matchingResource->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_relation_field_resources()
    {
        /**
         * @var Tag $matchingResource
         * @var Tag $notMatchingResource
         */
        $matchingResource = factory(Tag::class)->create()->refresh();
        $matchingResource->meta()->save(factory(TagMeta::class)->make(['key' => 'match']));
        $notMatchingResource = factory(Tag::class)->create()->refresh();
        $notMatchingResource->meta()->save(factory(TagMeta::class)->make(['key' => 'not match']));

        $response = $this->get('/api/tags?meta~key=match');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 1,
                'total' => 1
            ]
        ]);

        $response->assertJson([
            'data' => [$matchingResource->toArray()]
        ]);
    }

    /** @test */
    public function cannot_get_a_list_of_resources_filtered_by_not_whitelisted_field()
    {
        /**
         * @var Tag $matchingResourceA
         * @var Tag $matchingResourceB
         */
        $matchingResourceA = factory(Tag::class)->create(['description' => 'match'])->refresh();
        $matchingResourceB = factory(Tag::class)->create(['description' => 'not match'])->refresh();

        $response = $this->get('/api/tags?description=match');

        $this->assertResponseSuccessfulAndStructureIsValid($response);
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'to' => 2,
                'total' => 2
            ]
        ]);

        $response->assertJson([
            'data' => [$matchingResourceA->toArray(), $matchingResourceB->toArray()]
        ]);
    }

    /**
     * @param TestResponse $response
     */
    protected function assertResponseSuccessfulAndStructureIsValid($response)
    {
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total']
        ]);
    }
}
