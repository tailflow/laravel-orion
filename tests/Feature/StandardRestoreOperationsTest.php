<?php

namespace Orion\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Fixtures\App\Drivers\TwoRouteParameterKeyResolver;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\AccessKey;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class StandardRestoreOperationsTest extends TestCase
{
    /** @test */
    public function restoring_a_single_resource_without_authorization(): void
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        Gate::policy(Post::class, RedPolicy::class);

        $response = $this->post("/api/posts/{$trashedPost->id}/restore");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function restoring_a_single_resource_when_authorized(): void
    {
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => factory(User::class)->create()->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post("/api/posts/{$trashedPost->id}/restore");

        $this->assertResourceRestored($response, $trashedPost);
    }

    /** @test */
    public function restoring_a_single_resource_with_custom_keys(): void
    {
        $trashedAccessKey = factory(AccessKey::class)->state('trashed')->create();

        Gate::policy(AccessKey::class, GreenPolicy::class);

        $response = $this->post("/api/access_keys/{$trashedAccessKey->key}/restore");

        $this->assertResourceRestored($response, $trashedAccessKey);
    }

    /** @test */
    public function restoring_a_single_not_trashed_resource(): void
    {
        $post = factory(Post::class)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post("/api/posts/{$post->id}/restore");

        $this->assertResourceRestored($response, $post);
    }

    /** @test */
    public function restoring_a_single_resource_that_is_not_marked_as_soft_deletable(): void
    {
        $team = factory(Team::class)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post("/api/teams/{$team->id}/restore");

        $response->assertNotFound();
    }

    /** @test */
    public function transforming_a_single_restored_resource(): void
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        Gate::policy(Post::class, GreenPolicy::class);

        app()->bind(
            ComponentsResolver::class,
            function () {
                $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
                $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

                return $componentsResolverMock;
            }
        );

        $response = $this->post("/api/posts/{$trashedPost->id}/restore");

        $this->assertResourceRestored($response, $trashedPost, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function restoring_a_single_resource_and_getting_included_relation(): void
    {
        $user = factory(User::class)->create()->fresh();
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => $user->id])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post("/api/posts/{$trashedPost->id}/restore?include=user");

        $this->assertResourceRestored($response, $trashedPost, ['user' => $user->toArray()]);
    }

    /** @test */
    public function restoring_a_single_resource_with_multiple_route_parameters(): void
    {
        $this->useKeyResolver(TwoRouteParameterKeyResolver::class);

        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => factory(User::class)->create()->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post("/api/v1/posts/{$trashedPost->id}/restore");

        $this->assertResourceRestored($response, $trashedPost);
    }

    /** @test */
    public function restoring_a_single_resource_with_multiple_route_parameters_fails_with_default_key_resolver(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            $this->withoutExceptionHandling();
            $this->expectException(QueryException::class);
        }

        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => factory(User::class)->create()->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post("/api/v1/posts/{$trashedPost->id}/restore");

        $response->assertNotFound();
    }
}
