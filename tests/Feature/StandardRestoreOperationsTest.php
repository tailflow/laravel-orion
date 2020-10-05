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

class StandardRestoreOperationsTest extends TestCase
{
    /** @test */
    public function restoring_a_single_resource_without_authorization()
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        $response = $this->requireAuthorization()->post("/api/posts/{$trashedPost->id}/restore");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function restoring_a_single_resource_when_authorized()
    {
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => factory(User::class)->create()->id]);

        Gate::policy(Post::class, PostPolicy::class);

        $response = $this->requireAuthorization()->withAuth($trashedPost->user)->post("/api/posts/{$trashedPost->id}/restore");

        $this->assertResourceRestored($response, $trashedPost);
    }

    /** @test */
    public function restoring_a_single_not_trashed_resource()
    {
        $post = factory(Post::class)->create();

        $response = $this->post("/api/posts/{$post->id}/restore");

        $this->assertResourceRestored($response, $post);
    }

    /** @test */
    public function restoring_a_single_resource_that_is_not_marked_as_soft_deletable()
    {
        $team = factory(Team::class)->create();

        $response = $this->post("/api/teams/{$team->id}/restore");

        $response->assertNotFound();
    }

    /** @test */
    public function transforming_a_single_restored_resource()
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->post("/api/posts/{$trashedPost->id}/restore");

        $this->assertResourceRestored($response, $trashedPost, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function restoring_a_single_resource_and_getting_included_relation()
    {
        $user = factory(User::class)->create()->fresh();
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => $user->id])->fresh();

        $response = $this->bypassAuthorization()->post("/api/posts/{$trashedPost->id}/restore?include=user");

        $this->assertResourceRestored($response, $trashedPost, ['user' => $user->toArray()]);
    }
}
