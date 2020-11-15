<?php

namespace Orion\Tests\Feature\Relations\HasMany;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Company;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class HasManyRelationOneToManyOperationsTest extends TestCase
{
    /** @test */
    public function associating_relation_resource_when_parent_model_is_unauthorized(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create();

        Gate::policy(Company::class, RedPolicy::class);
        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams/associate", [
            'related_key' => $team->id
        ]);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function associating_relation_resource_when_relation_model_is_unauthorized(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create();

        Gate::policy(Company::class, GreenPolicy::class);
        Gate::policy(Team::class, RedPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams/associate", [
            'related_key' => $team->id
        ]);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function associating_relation_resource_with_no_related_key_provided(): void
    {
        $company = factory(Company::class)->create();
        factory(Team::class)->create();

        Gate::policy(Company::class, GreenPolicy::class);
        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams/associate");

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['related_key']]);
    }

    /** @test */
    public function associating_relation_resource_when_authorized(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create();

        Gate::policy(Company::class, GreenPolicy::class);
        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams/associate", [
            'related_key' => $team->id
        ]);

        $this->assertResourceAssociated($response, $company, $team, 'company');
    }

    /** @test */
    public function associating_relation_resource_and_getting_included_relation(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create();

        Gate::policy(Company::class, GreenPolicy::class);
        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams/associate?include=company", [
            'related_key' => $team->id
        ]);

        $this->assertResourceAssociated(
            $response,
            $company,
            $team,
            'company',
            ['company' => $company->fresh()->toArray()]
        );
    }

    /** @test */
    public function transforming_an_associated_relation_resource(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create();

        Gate::policy(Company::class, GreenPolicy::class);
        Gate::policy(Team::class, GreenPolicy::class);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->post("/api/companies/{$company->id}/teams/associate", [
            'related_key' => $team->id
        ]);

        $this->assertResourceAssociated(
            $response,
            $company,
            $team,
            'company',
            ['test-field-from-resource' => 'test-value']
        );
    }

    /** @test */
    public function dissociating_relation_resource_when_unauthorized(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);

        Gate::policy(Team::class, RedPolicy::class);

        $response = $this->delete("/api/companies/{$company->id}/teams/{$team->id}/dissociate");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function dissociating_relation_resource_when_authorized(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);

        Gate::policy(Company::class, GreenPolicy::class);
        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->delete("/api/companies/{$company->id}/teams/{$team->id}/dissociate");

        $this->assertResourceDissociated($response, $team, 'company');
    }

    /** @test */
    public function dissociating_relation_resource_and_getting_included_relation(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);

        Gate::policy(Company::class, GreenPolicy::class);
        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->delete("/api/companies/{$company->id}/teams/{$team->id}/dissociate?include=company");

        $this->assertResourceDissociated(
            $response,
            $team,
            'company',
            ['company' => null]
        );
    }

    /** @test */
    public function transforming_a_dissociated_relation_resource_when_authorized(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);

        Gate::policy(Company::class, GreenPolicy::class);
        Gate::policy(Team::class, GreenPolicy::class);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->delete("/api/companies/{$company->id}/teams/{$team->id}/dissociate");

        $this->assertResourceDissociated(
            $response,
            $team,
            'company',
            ['test-field-from-resource' => 'test-value']
        );
    }
}