<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\History;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\User;

class HandlesStandardUpdateOperationsTest extends TestCase
{
    /** @test */
    public function can_update_a_single_resource()
    {
        $tag = factory(Tag::class)->create();
        $payload = ['name' => 'test title', 'description' => 'test description'];

        $response = $this->patch("/api/tags/{$tag->id}", $payload);

        $this->assertResourceUpdated($response, $tag, $payload);
        $this->assertDatabaseHas('tags', $payload);
        $updatedTag = Tag::query()->first();
        $response->assertJson(['data' => $updatedTag->toArray()]);
    }

    /** @test */
    public function can_update_a_single_resource_with_only_fillable_properties()
    {
        $post = factory(Post::class)->create();
        $payload = ['title' => 'test post title updated', 'tracking_id' => 'test tracking id'];

        $response = $this->actingAs(factory(User::class)->create(), 'api')->patch("/api/posts/{$post->id}", $payload);

        $this->assertResourceUpdated($response, $post, ['title' => 'test post title updated']);
        $this->assertDatabaseMissing('posts', ['tracking_id' => 'test tracking id']);
        $updatedPost = Post::query()->first();
        $response->assertJson(['data' => $updatedPost->toArray()]);
    }

    /** @test */
    public function cannot_update_a_single_resource_if_validation_fails()
    {
        $tag = factory(Tag::class)->create();
        $payload = ['name' => 'test tag name updated'];

        $response = $this->patch("/api/tags/{$tag->id}", $payload, ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['description']]);
        $this->assertDatabaseMissing('tags', ['name' => 'test tag name updated']);
    }

    /** @test */
    public function can_update_a_single_resource_if_disable_authorization_trait_is_applied()
    {
        $tag = factory(Tag::class)->create();
        $payload = ['description' => 'test tag description updated'];

        $response = $this->patch("/api/tags/{$tag->id}", $payload);

        $this->assertResourceUpdated($response, $tag, $payload);
    }

    /** @test */
    public function cannot_update_a_single_resource_if_no_policy_is_defined_and_disable_authorizatin_trait_is_not_applied()
    {
        $supplier = factory(Supplier::class)->create();
        $history = factory(History::class)->create(['supplier_id' => $supplier->id]);
        $payload = ['code' => 'test history updated'];

        $response = $this->patch("/api/history/{$history->id}", $payload, ['Accept' => 'application/json']);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function can_update_a_single_resource_when_allowed_by_a_policy()
    {
        $post = factory(Post::class)->create();
        $payload = ['title' => 'test post title updated', 'body' => 'test post body updated'];

        $response = $this->actingAs(factory(User::class)->create(), 'api')->patch("/api/posts/{$post->id}", $payload);

        $this->assertResourceUpdated($response, $post, $payload);
    }

    /** @test */
    public function can_update_a_single_resource_transformed_by_resource()
    {
        $tagMeta = factory(TagMeta::class)->create(['tag_id' => factory(Tag::class)->create()->id]);
        $payload = ['key' => 'test key updated'];

        $response = $this->patch("/api/tag_meta/{$tagMeta->id}", $payload);

        $this->assertResourceUpdated($response, $tagMeta, $payload);
        $tagMeta = TagMeta::query()->first();
        $response->assertJson(['data' => array_merge($tagMeta->toArray(), ['test-field-from-resource' => 'test-value'])]);
    }
}
