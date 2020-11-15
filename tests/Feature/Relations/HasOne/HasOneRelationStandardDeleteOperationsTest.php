<?php

namespace Orion\Tests\Feature\Relations\HasOne;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\PostImage;
use Orion\Tests\Fixtures\App\Models\PostMeta;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class HasOneRelationStandardDeleteOperationsTest extends TestCase
{
    /** @test */
    public function trashing_a_single_soft_deletable_relation_resource_without_authorization(): void
    {
        $post = factory(Post::class)->create();
        factory(PostImage::class)->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, RedPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}/image");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function deleting_a_single_relation_resource_without_authorization(): void
    {
        $post = factory(Post::class)->create();
        factory(PostMeta::class)->create(['post_id' => $post->id]);

        Gate::policy(PostMeta::class, RedPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}/meta");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function force_deleting_a_single_relation_resource_without_authorization(): void
    {
        $post = factory(Post::class)->create();
        factory(PostImage::class)->state('trashed')->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, RedPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}/image?force=true");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function trashing_a_single_soft_deletable_relation_resource_when_authorized(): void
    {
        $post = factory(Post::class)->create();
        $postImage = factory(PostImage::class)->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}/image");

        $this->assertResourceTrashed($response, $postImage);
    }

    /** @test */
    public function deleting_a_single_relation_resource_when_authorized(): void
    {
        $post = factory(Post::class)->create();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id])->fresh();

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}/meta");

        $this->assertResourceDeleted($response, $postMeta);
    }

    /** @test */
    public function force_deleting_a_single_trashed_relation_resource_when_authorized(): void
    {
        $post = factory(Post::class)->create();
        $trashedPostImage = factory(PostImage::class)->state('trashed')->create(['post_id' => $post->id])->fresh();

        Gate::policy(PostImage::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}/image?force=true");

        $this->assertResourceDeleted($response, $trashedPostImage);
    }

    /** @test */
    public function deleting_a_single_trashed_relation_resource_without_trashed_query_parameter(): void
    {
        $post = factory(Post::class)->create();
        $trashedPostImage = factory(PostImage::class)->state('trashed')->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}/image");

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('post_images', $trashedPostImage->getAttributes());
    }

    /** @test */
    public function deleting_a_single_trashed_relation_resource_with_trashed_query_parameter(): void
    {
        $post = factory(Post::class)->create();
        $trashedPostImage = factory(PostImage::class)->state('trashed')->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}/image?with_trashed=true");

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('post_images', $trashedPostImage->getAttributes());
    }

    /** @test */
    public function transforming_a_single_deleted_relation_resource(): void
    {
        $post = factory(Post::class)->create();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id])->fresh();

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}/meta");

        $this->assertResourceDeleted($response, $postMeta, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function deleting_a_single_relation_resource_and_getting_included_relation(): void
    {
        $post = factory(Post::class)->create()->fresh();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id])->fresh();

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}/meta?include=post");

        $this->assertResourceDeleted($response, $postMeta, ['post' => $post->toArray()]);
    }
}