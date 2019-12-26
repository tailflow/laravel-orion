<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\History;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;

class HandlesStandardDeleteOperationsTest extends TestCase
{
    /** @test */
    public function can_delete_a_single_resource()
    {
        $tag = factory(Tag::class)->create();

        $response = $this->delete("/api/tags/{$tag->id}");

        $this->assertResourceDeleted($response, $tag);
        $response->assertJson(['data' => $tag->toArray()]);
    }

    /** @test */
    public function can_trash_a_single_soft_deletable_resource()
    {
        $team = factory(Team::class)->create();

        $response = $this->delete("/api/teams/{$team->id}", [], ['Accept' => 'application/json']);

        $this->assertResourceTrashed($response, $team);
        $response->assertJson(['data' => $team->toArray()]);
    }

    /** @test */
    public function can_delete_a_single_soft_deletable_resource()
    {
        $team = factory(Team::class)->create();

        $response = $this->delete("/api/teams/{$team->id}?force=true", [], ['Accept' => 'application/json']);

        $this->assertResourceDeleted($response, $team);
        $response->assertJson(['data' => $team->toArray()]);
    }

    /** @test */
    public function cannot_not_deleted_a_trashed_resource_without_trashed_query_parameter()
    {
        $trashedTeam = factory(Team::class)->state('trashed')->create();

        $response = $this->delete("/api/teams/{$trashedTeam->id}", [], ['Accept' => 'application/json']);

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('teams', $trashedTeam->toArray());
    }

    /** @test */
    public function cannot_delete_a_single_trashed_resource_with_trashed_query_parameter()
    {
        $trashedTeam = factory(Team::class)->state('trashed')->create();

        $response = $this->delete("/api/teams/{$trashedTeam->id}?with_trashed=true", [], ['Accept' => 'application/json']);

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('teams', $trashedTeam->toArray());
    }

    /** @test */
    public function can_delete_a_single_trashed_resource_with_force_query_parameter()
    {
        $trashedTeam = factory(Team::class)->state('trashed')->create();

        $response = $this->delete("/api/teams/{$trashedTeam->id}?force=true", [], ['Accept' => 'application/json']);

        $this->assertResourceDeleted($response, $trashedTeam);
        $response->assertJson(['data' => $trashedTeam->toArray()]);
    }

    /** @test */
    public function can_delete_a_single_resource_if_disable_authorization_trait_is_applied()
    {
        $tag = factory(Tag::class)->create();

        $response = $this->delete("/api/tags/{$tag->id}");

        $this->assertResourceDeleted($response, $tag);
    }

    /** @test */
    public function cannot_delete_a_single_resource_if_no_policy_is_defined_and_disable_authorizatin_trait_is_not_applied()
    {
        $supplier = factory(Supplier::class)->create();
        $history = factory(History::class)->create(['supplier_id' => $supplier->id]);

        $response = $this->delete("/api/history/{$history->id}", [], ['Accept' => 'application/json']);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function can_delete_a_single_resource_when_allowed_by_a_policy()
    {
        $post = factory(Post::class)->create();

        $response = $this->actingAs(factory(User::class)->create(), 'api')->delete("/api/posts/{$post->id}");

        $this->assertResourceDeleted($response, $post);
    }

    /** @test */
    public function can_delete_a_single_resource_transformed_by_resource()
    {
        $tagMeta  = factory(TagMeta::class)->create(['tag_id' => factory(Tag::class)->create()->id]);

        $response = $this->delete("/api/tag_meta/{$tagMeta->id}");

        $this->assertResourceDeleted($response, $tagMeta);
        $response->assertJson(['data' => array_merge($tagMeta->toArray(), ['test-field-from-resource' => 'test-value'])]);
    }
}
