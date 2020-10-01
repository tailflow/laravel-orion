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
use Orion\Tests\Fixtures\App\Policies\CategoryPolicy;
use Orion\Tests\Fixtures\App\Policies\UserPolicy;

class BelongsToStandardDeleteOperationsTest extends TestCase
{
    /** @test */
    public function trashing_a_single_soft_deletable_relation_resource_without_authorization()
    {
        $category = factory(Category::class)->create();
        $post = factory(Post::class)->create(['category_id' => $category->id]);

        $response = $this->requireAuthorization()->delete("/api/posts/{$post->id}/category");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function deleting_a_single_relation_resource_without_authorization()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        $response = $this->requireAuthorization()->delete("/api/posts/{$post->id}/user");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function force_deleting_a_single_relation_resource_without_authorization()
    {
        $trashedCategory = factory(Category::class)->state('trashed')->create();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        $response = $this->requireAuthorization()->delete("/api/posts/{$post->id}/category?force=true");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function trashing_a_single_soft_deletable_relation_resource_when_authorized()
    {
        $category = factory(Category::class)->create();
        $post = factory(Post::class)->create(['category_id' => $category->id]);

        Gate::policy(Category::class, CategoryPolicy::class);

        $response = $this->requireAuthorization()->withAuth($post->user)->delete("/api/posts/{$post->id}/category");

        $this->assertResourceTrashed($response, $category);
    }

    /** @test */
    public function deleting_a_single_relation_resource_when_authorized()
    {
        $user = factory(User::class)->create()->fresh();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        Gate::policy(User::class, UserPolicy::class);

        $response = $this->withAuth($user)->delete("/api/posts/{$post->id}/user");

        $this->assertResourceDeleted($response, $user);
    }

    /** @test */
    public function force_deleting_a_single_trashed_relation_resource_when_authorized()
    {
        $trashedCategory = factory(Category::class)->state('trashed')->create()->fresh();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        Gate::policy(Category::class, CategoryPolicy::class);

        $response = $this->requireAuthorization()->withAuth($post->user)->delete("/api/posts/{$post->id}/category?force=true");

        $this->assertResourceDeleted($response, $trashedCategory);
    }

    /** @test */
    public function deleting_a_single_trashed_relation_resource_without_trashed_query_parameter()
    {
        $trashedCategory = factory(Category::class)->state('trashed')->create();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        $response = $this->bypassAuthorization()->delete("/api/posts/{$post->id}/category");

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('categories', $trashedCategory->getAttributes());
    }

    /** @test */
    public function deleting_a_single_trashed_relation_resource_with_trashed_query_parameter()
    {
        $trashedCategory = factory(Category::class)->state('trashed')->create();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        $response = $this->bypassAuthorization()->delete("/api/posts/{$post->id}/category?with_trashed=true");

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('categories', $trashedCategory->getAttributes());
    }

    /** @test */
    public function deleting_a_single_trashed_relation_resource_with_force_query_parameter()
    {
        $trashedCategory = factory(Category::class)->state('trashed')->create()->fresh();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        $response = $this->bypassAuthorization()->delete("/api/posts/{$post->id}/category?force=true");

        $this->assertResourceDeleted($response, $trashedCategory);
    }

    /** @test */
    public function transforming_a_single_deleted_relation_resource()
    {
        $user = factory(User::class)->create()->fresh();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->withAuth($post->user)->delete("/api/posts/{$post->id}/user");

        $this->assertResourceDeleted($response, $user, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function deleting_a_single_relation_resource_and_getting_included_relation()
    {
        $user = factory(User::class)->create()->fresh();
        $post = factory(Post::class)->create(['user_id' => $user->id])->fresh();

        $response = $this->withAuth($user)->delete("/api/posts/{$post->id}/user?include=posts");

        $this->assertResourceDeleted($response, $user, ['posts' => [$post->toArray()]]);
    }
}