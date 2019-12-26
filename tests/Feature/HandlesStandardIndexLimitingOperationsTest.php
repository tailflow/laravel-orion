<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Collection;
use Orion\Tests\Fixtures\App\Models\Tag;

class HandlesStandardIndexLimitingOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_limited_list_of_resources_with_a_valid_limit_query_parameter()
    {
        /**
         * @var Collection $tags
         */
        $tags = factory(Tag::class)->times(15)->create();

        $response = $this->get('/api/tags?limit=5');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 3, 5, 5, 15);
        $response->assertJson([
            'data' => $tags->take(5)->values()->map(function ($tag) {
                /**
                 * @var Tag $tag
                 */
                return $tag->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_with_limit_query_parameter_being_a_string()
    {
        /**
         * @var Collection $tags
         */
        $tags = factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags?limit=is+a+string');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $tags->map(function ($tag) {
                /**
                 * @var Tag $tag
                 */
                return $tag->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_with_limit_query_parameter_being_a_negative_number()
    {
        /**
         * @var Collection $tags
         */
        $tags = factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags?limit=-1');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $tags->map(function ($tag) {
                /**
                 * @var Tag $tag
                 */
                return $tag->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_with_limit_query_parameter_being_zero()
    {
        /**
         * @var Collection $tags
         */
        $tags = factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags?limit=0');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $tags->map(function ($tag) {
                /**
                 * @var Tag $tag
                 */
                return $tag->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_with_limit_query_parameter_missing_value()
    {
        /**
         * @var Collection $tags
         */
        $tags = factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags?limit=');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $tags->map(function ($tag) {
                /**
                 * @var Tag $tag
                 */
                return $tag->toArray();
            })->toArray()
        ]);
    }
}
