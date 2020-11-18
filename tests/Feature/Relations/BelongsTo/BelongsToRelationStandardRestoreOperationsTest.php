<?php

namespace Orion\Tests\Feature\Relations\BelongsTo;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Category;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class BelongsToRelationStandardRestoreOperationsTest extends TestCase
{
    /** @test */
    public function restoring_a_single_relation_resource_without_authorization(): void
    {
        $trashedCategory = factory(Category::class)->state('trashed')->create();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        Gate::policy(Category::class, RedPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/category/{$trashedCategory->id}/restore");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function restoring_a_single_relation_resource_when_authorized(): void
    {
        $trashedCategory = factory(Category::class)->state('trashed')->create();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        Gate::policy(Category::class, GreenPolicy::class);

        $response =  $this->post("/api/posts/{$post->id}/category/{$trashedCategory->id}/restore");

        $this->assertResourceRestored($response, $trashedCategory);
    }

    /** @test */
    public function restoring_a_single_not_trashed_relation_resource(): void
    {
        $category = factory(Category::class)->create();
        $post = factory(Post::class)->create(['category_id' => $category->id]);

        Gate::policy(Category::class, GreenPolicy::class);

        $response =  $this->post("/api/posts/{$post->id}/category/{$category->id}/restore");

        $this->assertResourceRestored($response, $category);
    }

    /** @test */
    public function restoring_a_single_relation_resource_that_is_not_marked_as_soft_deletable(): void
    {
        $user = factory(User::class)->create()->fresh();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post("/api/teams/{$post->id}/user/{$user->id}/restore");

        $response->assertNotFound();
    }

    /** @test */
    public function transforming_a_single_restored_relation_resource(): void
    {
        $trashedCategory = factory(Category::class)->create();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        Gate::policy(Category::class, GreenPolicy::class);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response =  $this->post("/api/posts/{$post->id}/category/{$trashedCategory->id}/restore");

        $this->assertResourceRestored($response, $trashedCategory, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function restoring_a_single_relation_resource_and_getting_included_relation(): void
    {
        $trashedCategory = factory(Category::class)->create();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        Gate::policy(Category::class, GreenPolicy::class);

        $response =  $this->post("/api/posts/{$post->id}/category/{$trashedCategory->id}/restore?include=posts");

        $this->assertResourceRestored($response, $trashedCategory, ['posts' => $trashedCategory->posts->toArray()]);
    }
}