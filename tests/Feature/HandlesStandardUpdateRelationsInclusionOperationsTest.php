<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;

class HandlesStandardUpdateRelationsInclusionOperationsTest extends TestCase
{
    /** @test */
    public function can_update_a_single_resource_and_get_included_relation()
    {
        $tag = factory(Tag::class)->create(['team_id' => factory(Team::class)->create()->id]);
        $payload = ['description' => 'test description updated'];

        $response = $this->patch("/api/tags/{$tag->id}?include=team", $payload);

        $this->assertResourceUpdated($response, $tag, $payload);
        $tag = Tag::query()->with('team')->first();
        $response->assertJson(['data' => $tag->toArray()]);
    }

    /** @test */
    public function can_update_a_single_resource_but_not_get_included_relation_if_not_whitelisted()
    {
        $post = factory(Post::class)->create(['team_id' => factory(Team::class)->create()->id]);
        $payload = ['title' => 'test post title updated'];

        $response = $this->actingAs(factory(User::class)->create(), 'api')->patch("/api/posts/{$post->id}?include=team", $payload);

        $this->assertResourceUpdated($response, $post, $payload);
        $post = Post::query()->first();
        $response->assertJson(['data' => $post->toArray()]);
    }

    /** @test */
    public function cat_update_a_single_resource_and_get_always_included_relations()
    {
        $supplier = factory(Supplier::class)->create(['team_id' => factory(Team::class)->create()->id]);
        $payload = ['name' => 'test supplier updated'];

        $response = $this->patch("/api/suppliers/{$supplier->id}", $payload);

        $this->assertResourceUpdated($response, $supplier, $payload);
        $supplier = Supplier::query()->with('team')->first();
        $response->assertJson(['data' => $supplier->toArray()]);
    }
}
