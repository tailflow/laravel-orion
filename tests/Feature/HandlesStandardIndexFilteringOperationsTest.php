<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;

class HandlesStandardIndexFilteringOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources()
    {
        /**
         * @var Tag $matchingTag
         */
        $matchingTag = factory(Tag::class)->create(['name' => 'match'])->refresh();
        factory(Tag::class)->create(['name' => 'not match'])->refresh();

        $response = $this->get('/api/tags?name=match');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_relation_field_resources()
    {
        /**
         * @var Tag $matchingTag
         * @var Tag $notMatchingTag
         */
        $matchingTag = factory(Tag::class)->create()->refresh();
        $matchingTag->meta()->save(factory(TagMeta::class)->make(['key' => 'match']));
        $notMatchingTag = factory(Tag::class)->create()->refresh();
        $notMatchingTag->meta()->save(factory(TagMeta::class)->make(['key' => 'not match']));

        $response = $this->get('/api/tags?meta~key=match');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function cannot_get_a_list_of_resources_filtered_by_not_whitelisted_field()
    {
        /**
         * @var Tag $matchingTagA
         * @var Tag $notMatchingTagB
         */
        $matchingTagA = factory(Tag::class)->create(['description' => 'match'])->refresh();
        $notMatchingTagB = factory(Tag::class)->create(['description' => 'not match'])->refresh();

        $response = $this->get('/api/tags?description=match');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 2, 2);

        $response->assertJson([
            'data' => [$matchingTagA->toArray(), $notMatchingTagB->toArray()]
        ]);
    }
}
