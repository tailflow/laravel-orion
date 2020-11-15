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

class BelongsToRelationStandardShowOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_single_relation_resource_without_parent_authorization(): void
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        Gate::policy(User::class, RedPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/user");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function getting_a_single_relation_resource_when_authorized(): void
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/user");

        $this->assertResourceShown($response, $user);
    }

    /** @test */
    public function getting_a_single_trashed_relation_resource_when_with_trashed_query_parameter_is_missing(): void
    {
        $trashedCategory = factory(Category::class)->state('trashed')->create();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        Gate::policy(Category::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/category");

        $response->assertNotFound();
    }

    /** @test */
    public function getting_a_single_trashed_relation_resource_when_with_trashed_query_parameter_is_present(): void
    {
        $trashedCategory = factory(Category::class)->state('trashed')->create();
        $post = factory(Post::class)->create(['category_id' => $trashedCategory->id]);

        Gate::policy(Category::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/category?with_trashed=true");

        $this->assertResourceShown($response, $trashedCategory);
    }


    /** @test */
    public function getting_a_single_transformed_relation_resource(): void
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/user");

        $this->assertResourceShown($response, $user, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function getting_a_single_relation_resource_with_included_relation(): void
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}/user?include=posts");

        $this->assertResourceShown($response, $user->fresh('posts')->toArray());
    }
}