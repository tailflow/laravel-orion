<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\PostPolicy;
use Orion\Tests\Fixtures\App\Policies\TeamPolicy;

class StandardDeleteOperationsTest extends TestCase
{
    /** @test */
    public function trashing_a_single_soft_deletable_resource_without_authorization()
    {
        $post = factory(Post::class)->create();

        $response = $this->requireAuthorization()->delete("/api/posts/{$post->id}");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function deleting_a_single_resource_without_authorization()
    {
        $team = factory(Team::class)->create();

        $response = $this->delete("/api/teams/{$team->id}");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function force_deleting_a_single_resource_without_authorization()
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        $response = $this->requireAuthorization()->delete("/api/posts/{$trashedPost->id}?force=true");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function trashing_a_single_soft_deletable_resource_when_authorized()
    {
        $post = factory(Post::class)->create(['user_id' => factory(User::class)->create()->id]);

        Gate::policy(Post::class, PostPolicy::class);

        $response = $this->requireAuthorization()->withAuth($post->user)->delete("/api/posts/{$post->id}");

        $this->assertResourceTrashed($response, $post->fresh());
    }

    /** @test */
    public function deleting_a_single_resource_when_authorized()
    {
        $team = factory(Team::class)->create();

        Gate::policy(Team::class, TeamPolicy::class);

        $response = $this->withAuth()->delete("/api/teams/{$team->id}");

        $this->assertResourceDeleted($response, $team);
    }

    /** @test */
    public function force_deleting_a_single_resource_when_authorized()
    {
        $user = factory(User::class)->create();
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => $user->id]);

        Gate::policy(Post::class, PostPolicy::class);

        $response = $this->requireAuthorization()->withAuth($user)->delete("/api/posts/{$trashedPost->id}?force=true");

        $this->assertResourceDeleted($response, $trashedPost);
    }

    /** @test */
    public function deleting_a_single_trashed_resource_without_trashed_query_parameter()
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        $response = $this->delete("/api/posts/{$trashedPost->id}");

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('posts', $trashedPost->getAttributes());
    }

    /** @test */
    public function deleting_a_single_trashed_resource_with_trashed_query_parameter()
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        $response = $this->delete("/api/posts/{$trashedPost->id}?with_trashed=true");

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('posts', $trashedPost->getAttributes());
    }

    /** @test */
    public function deleting_a_single_trashed_resource_with_force_query_parameter()
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        $response = $this->delete("/api/posts/{$trashedPost->id}?force=true");

        $this->assertResourceDeleted($response, $trashedPost);
    }

    /** @test */
    public function transforming_a_single_deleted_resource()
    {
        $post = factory(Post::class)->create();

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->delete("/api/posts/{$post->id}?force=true");

        $this->assertResourceDeleted($response, $post, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function deleting_a_single_resource_and_getting_included_relation()
    {
        $post = factory(Post::class)->create(['user_id' => factory(User::class)->create()->id]);

        $response = $this->delete("/api/posts/{$post->id}?force=true&include=user");

        $post->load('user');
        $this->assertResourceDeleted($response, $post);
    }
}
