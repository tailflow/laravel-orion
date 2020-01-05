<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Collection;
use Orion\Tests\Fixtures\App\Models\Tag;

class HandlesStandardIndexScopingOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_list_of_scoped_resources_without_parameters()
    {
        /**
         * @var Tag $matchingTag
         */
        $matchingTag = factory(Tag::class)->create(['priority' => 1])->refresh();
        factory(Tag::class)->create(['name' => 'not match'])->refresh();

        $response = $this->post('/api/tags/search', [
            'scopes' => [
                ['name' => 'withPriority']
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_scoped_resources_with_parameters()
    {
        /**
         * @var Tag $matchingTag
         */
        $matchingTag = factory(Tag::class)->create(['name' => 'match', 'priority' => 1])->refresh();
        factory(Tag::class)->create(['name' => 'match'])->refresh();

        $response = $this->post('/api/tags/search', [
            'scopes' => [
                ['name' => 'whereNameAndPriority', 'parameters' => ['match', 1]]
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function cannnot_get_a_list_of_scoped_resources_if_scope_is_not_whitelisted()
    {
        /**
         * @var Collection $tags
         */
        factory(Tag::class)->times(5)->create();

        $response = $this->post('/api/tags/search', [
            'scopes' => [
                ['name' => 'withoutPriority']
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['scopes.0.name']]);
    }
}
