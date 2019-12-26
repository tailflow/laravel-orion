<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;

class HandlesStandardStoreRelationsInclusionOperationsTest extends TestCase
{
    /** @test */
    public function can_store_a_single_resource_and_get_included_relation()
    {
        $payload = ['name' => 'test tag', 'description' => 'test description', 'team_id' => factory(Team::class)->create()->id];

        $response = $this->post('/api/tags?include=team', $payload);

        $this->assertSuccessfulStoreResponse($response);
        $tag = Tag::query()->with('team')->first();
        $response->assertJson(['data' => $tag->toArray()]);
    }

    /** @test */
    public function can_store_a_single_resource_but_not_get_included_relation_if_not_whitelisted()
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body', 'team_id' => factory(Team::class)->create()->id];

        $response = $this->actingAs(factory(User::class)->create(), 'api')->post('/api/posts?include=team', $payload);

        $this->assertSuccessfulStoreResponse($response);
        $post = Post::query()->first();
        $response->assertJson(['data' => $post->toArray()]);
    }

    /** @test */
    public function cat_store_a_single_resource_and_get_always_included_relations()
    {
        $payload = ['name' => 'test supplier', 'team_id' => factory(Team::class)->create()->id];

        $response = $this->post('/api/suppliers', $payload);

        $this->assertSuccessfulStoreResponse($response);
        $supplier = Supplier::query()->with('team')->first();
        $response->assertJson(['data' => $supplier->toArray()]);
    }
}
