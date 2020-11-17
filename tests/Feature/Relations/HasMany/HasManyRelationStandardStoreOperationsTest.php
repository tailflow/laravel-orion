<?php

namespace Orion\Tests\Feature\Relations\HasMany;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Requests\TeamRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Company;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class HasManyRelationStandardStoreOperationsTest extends TestCase
{
    /** @test */
    public function storing_a_single_relation_resource_without_authorization(): void
    {
        $company = factory(Company::class)->create();
        $payload = ['name' => 'test stored'];

        Gate::policy(Team::class, RedPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams", $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function storing_a_single_relation_resource_when_authorized(): void
    {
        $company = factory(Company::class)->create();
        $payload = ['name' => 'test stored'];

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams", $payload);

        $this->assertResourceStored($response, Team::class, $payload);
    }

    /** @test */
    public function storing_a_single_relation_resource_with_only_fillable_fields(): void
    {
        $company = factory(Company::class)->create();
        $payload = ['name' => 'test stored', 'active' => false];

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams", $payload);

        $this->assertResourceStored($response,
            Team::class,
            ['name' => 'test stored']
        );
        $this->assertDatabaseMissing('teams', ['active' => false]);
        $response->assertJsonMissing(['active' => false]);
    }

    /** @test */
    public function storing_a_single_relation_resource_when_validation_fails(): void
    {
        $company = factory(Company::class)->create();
        $payload = ['name' => 'test stored', 'description' => 5];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(TeamRequest::class);

            return $componentsResolverMock;
        });

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams", $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['description']]);
        $this->assertDatabaseMissing('teams', ['description' => 5]);
    }

    /** @test */
    public function transforming_a_single_stored_relation_resource(): void
    {
        $company = factory(Company::class)->create();
        $payload = ['name' => 'test stored'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams", $payload);

        $this->assertResourceStored($response, Team::class, $payload, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function storing_a_single_relation_resource_and_getting_included_relation(): void
    {
        $company = factory(Company::class)->create();
        $payload = ['name' => 'test stored'];

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post("/api/companies/{$company->id}/teams?include=company", $payload);

        $this->assertResourceStored($response, Team::class, $payload, ['company' => $company->fresh()->toArray()]);
    }
}