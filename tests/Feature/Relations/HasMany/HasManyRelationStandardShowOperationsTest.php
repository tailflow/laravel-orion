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

class HasManyRelationStandardShowOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_single_relation_resource_without_parent_authorization()
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);

        Gate::policy(Team::class, RedPolicy::class);

        $response = $this->get("/api/companies/{$company->id}/teams/{$team->id}");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function getting_a_single_relation_resource_when_authorized()
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->get("/api/companies/{$company->id}/teams/{$team->id}");

        $this->assertResourceShown($response, $team);
    }

    /** @test */
    public function getting_a_single_trashed_relation_resource_when_with_trashed_query_parameter_is_missing()
    {
        $user = factory(User::class)->create();
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/posts/{$trashedPost->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function getting_a_single_trashed_relation_resource_when_with_trashed_query_parameter_is_present()
    {
        $user = factory(User::class)->create();
        $trashedPost = factory(Post::class)->state('trashed')->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/posts/{$trashedPost->id}?with_trashed=true");

        $this->assertResourceShown($response, $trashedPost);
    }


    /** @test */
    public function getting_a_single_transformed_relation_resource()
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->get("/api/companies/{$company->id}/teams/{$team->id}");

        $this->assertResourceShown($response, $team, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function getting_a_single_relation_resource_with_included_relation()
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->get("/api/companies/{$company->id}/teams/{$team->id}?include=company");

        $this->assertResourceShown($response, $team->fresh('company')->toArray());
    }
}