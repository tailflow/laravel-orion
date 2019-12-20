<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\History;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\User;

class HandlesStandardShowOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_single_resource()
    {
        $tag = factory(Tag::class)->create();

        $response = $this->get("/api/tags/{$tag->id}");

        $this->assertSuccessfulShowResponse($response);
        $response->assertJson(['data' => $tag->toArray()]);
    }

    /** @test */
    public function can_get_a_list_of_resources_if_disable_authorization_trait_is_applied()
    {
        $tag = factory(Tag::class)->create();

        $response = $this->get("/api/tags/{$tag->id}");

        $this->assertSuccessfulShowResponse($response);
    }

    /** @test */
    public function cannot_get_a_single_resource_if_no_policy_is_defined_and_disable_authorizatin_trait_is_not_applied()
    {
        $supplier = factory(Supplier::class)->create();
        $history = factory(History::class)->create(['supplier_id' => $supplier]);

        $response = $this->get("/api/history/{$history->id}", ['Accept' => 'application/json']);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function can_get_a_single_resource_when_allowed_by_a_policy()
    {
        $post = factory(Post::class)->create();

        $response = $this->actingAs(factory(User::class)->create(), 'api')->get("/api/posts/{$post->id}");

        $this->assertSuccessfulShowResponse($response);
        $response->assertJson(['data' => $post->toArray()]);
    }
}
