<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\History;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\Team;
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
    public function cannot_not_see_trashed_resources_if_query_parameters_are_missing()
    {
        $trashedTeam = factory(Team::class)->state('trashed')->create();

        $response = $this->get("/api/teams/{$trashedTeam->id}", ['Accept' => 'application/json']);

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
    }

    /** @test */
    public function can_get_a_single_soft_delatable_resource_with_trashed()
    {
        $trashedTeam = factory(Team::class)->state('trashed')->create();

        $response = $this->get("/api/teams/{$trashedTeam->id}?with_trashed=true");

        $this->assertSuccessfulShowResponse($response);
        $response->assertJson(['data' => $trashedTeam->toArray()]);
    }

    /** @test */
    public function can_get_a_single_resource_if_disable_authorization_trait_is_applied()
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
    }

    /** @test */
    public function can_get_a_single_transformed_resource()
    {
        $tagMeta = factory(TagMeta::class)->create(['tag_id' => factory(Tag::class)->create()->id]);

        $response = $this->get("/api/tag_meta/{$tagMeta->id}");

        $this->assertSuccessfulShowResponse($response);
        $response->assertJson(['data' => array_merge($tagMeta->toArray(), ['test-field-from-resource' => 'test-value'])]);
    }
}
