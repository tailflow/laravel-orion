<?php

namespace Orion\Tests\Feature;

use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Support\Collection;
use Orion\Tests\Fixtures\App\Models\Tag;

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
    public function can_get_a_limited_list_of_resources_with_valid_limit_query_parameter()
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

        $this->withoutExceptionHandling();

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

        $this->withoutExceptionHandling();

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
