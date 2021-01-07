<?php

namespace Orion\Tests\Feature\Relations\HasMany;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Resources\SampleCollectionResource;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Company;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class HasManyRelationStandardIndexOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_relation_resources_without_authorization(): void
    {
        $company = factory(Company::class)->create();
        factory(Team::class)->times(5)->create(['company_id' => $company->id]);

        Gate::policy(Team::class, RedPolicy::class);

        $response = $this->get("/api/companies/{$company->id}/teams");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function getting_a_list_of_relation_resources_when_authorized(): void
    {
        $company = factory(Company::class)->create();
        $teams = factory(Team::class)->times(5)->create(['company_id' => $company->id]);

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->get("/api/companies/{$company->id}/teams");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($teams, "companies/{$company->id}/teams")
        );
    }

    /** @test */
    public function getting_a_list_of_relation_resources_without_pagination(): void
    {
        $company = factory(Company::class)->create();
        $teams = factory(Team::class)->times(20)->create(['company_id' => $company->id]);

        Gate::policy(Team::class, GreenPolicy::class);

        app()->bind('orion.paginationEnabled', function() {
            return false;
        });

        $response = $this->get("/api/companies/{$company->id}/teams");

        $this->assertResourcesListed(
            $response,
            $teams
        );
    }

    /** @test */
    public function getting_a_paginated_list_of_relation_resources(): void
    {
        $company = factory(Company::class)->create();
        $teams = factory(Team::class)->times(45)->create(['company_id' => $company->id]);

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->get("/api/companies/{$company->id}/teams?page=2");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($teams, "companies/{$company->id}/teams", 2)
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_relation_resources_when_with_trashed_query_parameter_is_present(): void
    {
        $user = factory(User::class)->create();
        $trashedPosts = factory(Post::class)->state('trashed')->times(5)->create(['user_id' => $user->id]);
        $posts = factory(Post::class)->times(5)->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/posts?with_trashed=true");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($trashedPosts->merge($posts), "users/{$user->id}/posts")
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_relation_resources_when_only_trashed_query_parameter_is_present(): void
    {
        $user = factory(User::class)->create();
        $trashedPosts = factory(Post::class)->state('trashed')->times(5)->create(['user_id' => $user->id]);
        factory(Post::class)->times(5)->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/posts?only_trashed=true");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($trashedPosts, "users/{$user->id}/posts")
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_relation_resources_with_trashed_resources_filtered_out(): void
    {
        $user = factory(User::class)->create();
        $posts = factory(Post::class)->times(5)->create(['user_id' => $user->id]);
        $trashedPosts = factory(Post::class)->state('trashed')->times(5)->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/posts");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, "users/{$user->id}/posts")
        );
        $response->assertJsonMissing([
            'data' => $trashedPosts->map(function (Post $post) {
                return $post->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function transforming_a_list_of_relation_resources(): void
    {
        $company = factory(Company::class)->create();
        $teams = factory(Team::class)->times(5)->create(['company_id' => $company->id]);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->get("/api/companies/{$company->id}/teams");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($teams, "companies/{$company->id}/teams"),
            ['test-field-from-resource' => 'test-value']
        );
    }

    /** @test */
    public function transforming_a_list_of_relation_resources_using_collection_resource(): void
    {
        $company = factory(Company::class)->create();
        $teams = factory(Team::class)->times(5)->create(['company_id' => $company->id]);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveCollectionResourceClass')->once()->andReturn(SampleCollectionResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->get("/api/companies/{$company->id}/teams");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($teams, "companies/{$company->id}/teams"),
            [],
            false
        );
        $response->assertJson([
            'test-field-from-resource' => 'test-value'
        ]);
    }

    /** @test */
    public function getting_a_list_of_relation_resources_with_included_relation(): void
    {
        $company = factory(Company::class)->create();
        $teams = factory(Team::class)->times(5)->create()->map(function (Team $team) use ($company) {
            $team->company()->associate($company);
            $team->save();
            $team->refresh();

            return $team->toArray();
        });

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->get("/api/companies/{$company->id}/teams?include=company");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($teams, "companies/{$company->id}/teams")
        );
    }
}
