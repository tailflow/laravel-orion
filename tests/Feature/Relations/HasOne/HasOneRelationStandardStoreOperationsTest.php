<?php

declare(strict_types=1);

namespace Orion\Tests\Feature\Relations\HasOne;

use Illuminate\Support\Facades\Gate;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Requests\PostMetaRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\PostMeta;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class HasOneRelationStandardStoreOperationsTest extends TestCase
{
    /** @test */
    public function storing_a_single_relation_resource_without_authorization(): void
    {
        $post = factory(Post::class)->create();
        $payload = ['notes' => 'test stored'];

        Gate::policy(PostMeta::class, RedPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/meta", $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function storing_a_single_relation_resource_when_authorized(): void
    {
        $post = factory(Post::class)->create();
        $payload = ['notes' => 'test stored'];

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/meta", $payload);

        $this->assertResourceStored($response, PostMeta::class, $payload);
    }

    /** @test */
    public function attempting_to_store_a_resource_over_an_existing_resource(): void
    {
        $post = factory(Post::class)->create();
        $payload = ['notes' => 'test stored'];

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/meta", $payload);

        $this->assertResourceStored($response, PostMeta::class, $payload);
    }

    /** @test */
    public function storing_a_single_relation_resource_with_only_fillable_fields(): void
    {
        $post = factory(Post::class)->create();
        $payload = ['notes' => 'test stored', 'comments_enabled' => false];

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/meta", $payload);

        $this->assertResourceStored(
            $response,
            PostMeta::class,
            ['notes' => 'test stored']
        );
        $this->assertDatabaseMissing('post_metas', ['comments_enabled' => false]);
        $response->assertJsonMissing(['comments_enabled' => false]);
    }

    /** @test */
    public function storing_a_single_relation_resource_when_validation_fails(): void
    {
        $post = factory(Post::class)->create();
        $payload = ['notes' => 'a'];

        $this->useRequest(PostMeta::class, PostMetaRequest::class);

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/meta", $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['notes']]);
        $this->assertDatabaseMissing('post_metas', ['notes' => 'a']);
    }

    /** @test */
    public function transforming_a_single_stored_relation_resource(): void
    {
        $post = factory(Post::class)->create();

        $postMeta = factory(PostMeta::class)->make();
        $postMeta->post()->associate($post);
        $postMeta->save();

        $payload = ['notes' => 'test stored'];

        $this->useResource(PostMeta::class, SampleResource::class);

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/meta", $payload);

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Entity already exists.']);
    }

    /** @test */
    public function storing_a_single_relation_resource_and_getting_included_relation(): void
    {
        $post = factory(Post::class)->create();
        $payload = ['notes' => 'test stored'];

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/meta?include=post", $payload);

        $this->assertResourceStored($response, PostMeta::class, $payload, ['post' => $post->fresh()->toArray()]);
    }
}
