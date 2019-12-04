<?php


namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;

class HandlesStandardIndexSearchingOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_list_of_searched_by_model_field_resources()
    {
        /**
         * @var Tag $matchingTag
         */
        $matchingTag = factory(Tag::class)->create(['name' => 'matching'])->refresh();
        factory(Tag::class)->create(['name' => 'test'])->refresh();

        $response = $this->get('/api/tags?q=match');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_searched_by_relation_field_resources()
    {
        /**
         * @var Tag $matchingTag
         * @var Tag $notMatchingTag
         */
        $matchingTag = factory(Tag::class)->create()->refresh();
        $matchingTag->meta()->save(factory(TagMeta::class)->make(['key' => 'matching']));
        $notMatchingTag = factory(Tag::class)->create()->refresh();
        $notMatchingTag->meta()->save(factory(TagMeta::class)->make(['key' => 'test']));

        $response = $this->get('/api/tags?q=match');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_with_empty_search_query_parameter()
    {
        /**
         * @var Tag $matchingTagA
         */
        $matchingTagA = factory(Tag::class)->create(['name' => 'matching'])->refresh();
        $matchingTagB = factory(Tag::class)->create(['name' => 'test'])->refresh();

        $response = $this->get('/api/tags?q=');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 2, 2);

        $response->assertJson([
            'data' => [$matchingTagA->toArray(), $matchingTagB->toArray()]
        ]);
    }
}
