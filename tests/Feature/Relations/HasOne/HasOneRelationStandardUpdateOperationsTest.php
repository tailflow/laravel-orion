<?php

namespace Orion\Tests\Feature\Relations\HasOne;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Requests\PostMetaRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\PostMeta;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class HasOneRelationStandardUpdateOperationsTest extends TestCase
{
    /** @test */
    public function updating_a_single_relation_resource_without_authorization(): void
    {
        $post = factory(Post::class)->create();
        factory(PostMeta::class)->create(['post_id' => $post->id]);
        $payload = ['notes' => 'test updated'];

        Gate::policy(PostMeta::class, RedPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}/meta", $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function updating_a_single_relation_resource_when_authorized(): void
    {
        $post = factory(Post::class)->create();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id]);
        $payload = ['notes' => 'test updated'];

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}/meta", $payload);

        $this->assertResourceUpdated($response,
            PostMeta::class,
            $postMeta->toArray(),
            $payload
        );
    }

    /** @test */
    public function updating_a_single_relation_resource_with_only_fillable_fields(): void
    {
        $post = factory(Post::class)->create();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id]);
        $payload = ['notes' => 'test updated', 'comments_enabled' => false];

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}/meta", $payload);

        $this->assertResourceUpdated($response,
            PostMeta::class,
            $postMeta->toArray(),
            ['notes' => 'test updated']
        );
        $this->assertDatabaseMissing('post_metas', ['comments_enabled' => false]);
        $response->assertJsonMissing(['comments_enabled' => false]);
    }

    /** @test */
    public function updating_a_single_relation_resource_when_validation_fails(): void
    {
        $post = factory(Post::class)->create();
        factory(PostMeta::class)->create(['post_id' => $post->id]);
        $payload = ['notes' => 'abc'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(PostMetaRequest::class);

            return $componentsResolverMock;
        });

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}/meta", $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['notes']]);
        $this->assertDatabaseMissing('post_metas', ['notes' => 'abc']);
    }

    /** @test */
    public function transforming_a_single_updated_relation_resource(): void
    {
        $post = factory(Post::class)->create();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id]);
        $payload = ['notes' => 'test updated'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}/meta", $payload);

        $this->assertResourceUpdated($response,
            PostMeta::class,
            $postMeta->toArray(),
            $payload,
            ['test-field-from-resource' => 'test-value']
        );
    }

    /** @test */
    public function updating_a_single_resource_and_getting_included_relation(): void
    {
        $post = factory(Post::class)->create();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id]);
        $payload = ['notes' => 'test updated'];

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}/meta?include=post", $payload);

        $this->assertResourceUpdated($response,
            PostMeta::class,
            $postMeta->toArray(),
            $payload,
            ['post' => $postMeta->fresh('post')->post->toArray()]
        );
    }
}