<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;

class HandlesStandardDeleteRelationsInclusionOperationsTest extends TestCase
{
    /** @test */
    public function can_delete_a_single_resource_and_get_included_relation()
    {
        $tag = factory(Tag::class)->create(['team_id' => factory(Team::class)->create()->id]);

        $response = $this->delete("/api/tags/{$tag->id}?include=team");

        $this->assertResourceDeleted($response, $tag);
        $tag->load('team');
        $response->assertJson(['data' => $tag->toArray()]);
    }

    /** @test */
    public function can_delete_a_single_resource_but_not_get_included_relation_if_not_whitelisted()
    {
        $post = factory(Post::class)->create(['team_id' => factory(Team::class)->create()->id]);

        $response = $this->actingAs(factory(User::class)->create(), 'api')->delete("/api/posts/{$post->id}?include=team");

        $this->assertResourceDeleted($response, $post);
        $response->assertJson(['data' => $post->toArray()]);
    }

    /** @test */
    public function cat_delete_a_single_resource_and_get_always_included_relations()
    {
        $supplier = factory(Supplier::class)->create(['team_id' => factory(Team::class)->create()->id]);

        $response = $this->delete("/api/suppliers/{$supplier->id}");

        $this->assertResourceDeleted($response, $supplier);
        $supplier->load('team');
        $response->assertJson(['data' => $supplier->toArray()]);
    }
}
