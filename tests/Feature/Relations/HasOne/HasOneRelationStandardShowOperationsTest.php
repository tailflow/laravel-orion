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

class HasOneRelationStandardShowOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_single_relation_resource_without_parent_authorization(): void
    {
        $post = factory(Post::class)->create();
        factory(PostMeta::class)->create(['post_id' => $post->id]);

        Gate::policy(PostMeta::class, RedPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/meta");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function getting_a_single_relation_resource_when_authorized(): void
    {
        $post = factory(Post::class)->create();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id]);

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/meta");

        $this->assertResourceShown($response, $postMeta);
    }

    /** @test */
    public function getting_a_single_trashed_relation_resource_when_with_trashed_query_parameter_is_missing(): void
    {
        $post = factory(Post::class)->create();
        factory(PostImage::class)->state('trashed')->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/image");

        $response->assertNotFound();
    }

    /** @test */
    public function getting_a_single_trashed_relation_resource_when_with_trashed_query_parameter_is_present(): void
    {
        $post = factory(Post::class)->create();
        $trashedPostMeta =  factory(PostImage::class)->state('trashed')->create(['post_id' => $post->id]);

        Gate::policy(PostImage::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/image?with_trashed=true");

        $this->assertResourceShown($response, $trashedPostMeta);
    }


    /** @test */
    public function getting_a_single_transformed_relation_resource(): void
    {
        $post = factory(Post::class)->create();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id]);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/meta");

        $this->assertResourceShown($response, $postMeta, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function getting_a_single_relation_resource_with_included_relation(): void
    {
        $post = factory(Post::class)->create();
        $postMeta = factory(PostMeta::class)->create(['post_id' => $post->id]);

        Gate::policy(PostMeta::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/meta?include=post");

        $this->assertResourceShown($response, $postMeta->fresh('post')->toArray());
    }
}