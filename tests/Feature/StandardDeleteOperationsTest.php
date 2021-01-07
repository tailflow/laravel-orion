<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\AccessKey;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class StandardDeleteOperationsTest extends TestCase
{
    /** @test */
    public function trashing_a_single_soft_deletable_resource_without_authorization(): void
    {
        $post = factory(Post::class)->create();

        Gate::policy(Post::class, RedPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function deleting_a_single_resource_without_authorization(): void
    {
        $team = factory(Team::class)->create();

        Gate::policy(Team::class, RedPolicy::class);

        $response = $this->delete("/api/teams/{$team->id}");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function force_deleting_a_single_resource_without_authorization(): void
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        Gate::policy(Post::class, RedPolicy::class);

        $response = $this->delete("/api/posts/{$trashedPost->id}?force=true");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function trashing_a_single_soft_deletable_resource_when_authorized(): void
    {
        $post = factory(Post::class)->create(['user_id' => factory(User::class)->create()->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}");

        $this->assertResourceTrashed($response, $post);
    }

    /** @test */
    public function trashing_a_single_soft_deletable_resource_with_custom_key(): void
    {
        $accessKey = factory(AccessKey::class)->create();

        Gate::policy(AccessKey::class, GreenPolicy::class);

        $response = $this->delete("/api/access_keys/{$accessKey->key}");

        $this->assertResourceTrashed($response, $accessKey);
    }

    /** @test */
    public function deleting_a_single_resource_when_authorized(): void
    {
        $team = factory(Team::class)->create()->fresh();

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->delete("/api/teams/{$team->id}");

        $this->assertResourceDeleted($response, $team);
    }

    /** @test */
    public function force_deleting_a_single_trashed_resource_when_authorized(): void
    {
        $user = factory(User::class)->create();
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => $user->id])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$trashedPost->id}?force=true");

        $this->assertResourceDeleted($response, $trashedPost);
    }

    /** @test */
    public function deleting_a_single_trashed_resource_without_trashed_query_parameter(): void
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$trashedPost->id}");

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('posts', $trashedPost->getAttributes());
    }

    /** @test */
    public function deleting_a_single_trashed_resource_with_trashed_query_parameter(): void
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$trashedPost->id}?with_trashed=true");

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('posts', $trashedPost->getAttributes());
    }

    /** @test */
    public function deleting_a_single_trashed_resource_with_force_query_parameter(): void
    {
        $trashedPost = factory(Post::class)->state('trashed')->create()->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$trashedPost->id}?force=true");

        $this->assertResourceDeleted($response, $trashedPost);
    }

    /** @test */
    public function transforming_a_single_deleted_resource(): void
    {
        $post = factory(Post::class)->create()->fresh();

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}?force=true");

        $this->assertResourceDeleted($response, $post, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function deleting_a_single_resource_and_getting_included_relation(): void
    {
        $user = factory(User::class)->create()->fresh();
        $post = factory(Post::class)->create(['user_id' => $user->id])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->delete("/api/posts/{$post->id}?force=true&include=user");

        $this->assertResourceDeleted($response, $post, ['user' => $user->toArray()]);
    }
}
