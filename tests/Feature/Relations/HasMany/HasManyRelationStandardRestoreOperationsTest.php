<?php

namespace Orion\Tests\Feature\Relations\HasMany;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Company;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class HasManyRelationStandardRestoreOperationsTest extends TestCase
{
    /** @test */
    public function restoring_a_single_relation_resource_without_authorization()
    {
        $user = factory(User::class)->create();
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => $user->id]);

        Gate::policy(Post::class, RedPolicy::class);

        $response = $this->post("/api/users/{$user->id}/posts/{$trashedPost->id}/restore");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function restoring_a_single_relation_resource_when_authorized()
    {
        $user = factory(User::class)->create();
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/posts/{$trashedPost->id}/restore");

        $this->assertResourceRestored($response, $trashedPost);
    }

    /** @test */
    public function restoring_a_single_not_trashed_relation_resource()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/posts/{$post->id}/restore");

        $this->assertResourceRestored($response, $post);
    }

    /** @test */
    public function restoring_a_single_relation_resource_that_is_not_marked_as_soft_deletable()
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams/{$team->id}/restore");

        $response->assertNotFound();
    }

    /** @test */
    public function transforming_a_single_restored_relation_resource()
    {
        $user = factory(User::class)->create();
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->post("/api/users/{$user->id}/posts/{$trashedPost->id}/restore");

        $this->assertResourceRestored($response, $trashedPost, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function restoring_a_single_relation_resource_and_getting_included_relation()
    {
        $user = factory(User::class)->create()->fresh();
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/posts/{$trashedPost->id}/restore?include=user");

        $this->assertResourceRestored($response, $trashedPost, ['user' => $user->toArray()]);
    }
}