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

class HasOneRelationStandardRestoreOperationsTest extends TestCase
{
    /** @test */
    public function restoring_a_single_relation_resource_without_authorization(): void
    {
        $post = factory(Post::class)->create();
        $trashedPostImage = factory(PostImage::class)->state('trashed')->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, RedPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/image/{$trashedPostImage->id}/restore");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function restoring_a_single_relation_resource_when_authorized(): void
    {
        $post = factory(Post::class)->create();
        $trashedPostImage = factory(PostImage::class)->state('trashed')->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, GreenPolicy::class);

        $response =  $this->post("/api/posts/{$post->id}/image/{$trashedPostImage->id}/restore");

        $this->assertResourceRestored($response, $trashedPostImage);
    }

    /** @test */
    public function restoring_a_single_not_trashed_relation_resource(): void
    {
        $post = factory(Post::class)->create();
        $postImage = factory(PostImage::class)->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, GreenPolicy::class);

        $response =  $this->post("/api/posts/{$post->id}/image/{$postImage->id}/restore");

        $this->assertResourceRestored($response, $postImage);
    }

    /** @test */
    public function restoring_a_single_relation_resource_that_is_not_marked_as_soft_deletable(): void
    {
        $post = factory(Post::class)->create();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id])->fresh();

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/meta/{$postMeta->id}/restore");

        $response->assertNotFound();
    }

    /** @test */
    public function transforming_a_single_restored_relation_resource(): void
    {
        $post = factory(Post::class)->create();
        $trashedPostImage = factory(PostImage::class)->state('trashed')->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, GreenPolicy::class);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response =  $this->post("/api/posts/{$post->id}/image/{$trashedPostImage->id}/restore");

        $this->assertResourceRestored($response, $trashedPostImage, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function restoring_a_single_relation_resource_and_getting_included_relation(): void
    {
        $post = factory(Post::class)->create();
        $trashedPostImage = factory(PostImage::class)->state('trashed')->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, GreenPolicy::class);

        $response =  $this->post("/api/posts/{$post->id}/image/{$trashedPostImage->id}/restore?include=post");

        $this->assertResourceRestored($response, $trashedPostImage, ['post' => $trashedPostImage->post->toArray()]);
    }
}