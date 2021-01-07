<?php

namespace Orion\Tests\Feature\Relations\HasMany;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Requests\TeamRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\AccessKey;
use Orion\Tests\Fixtures\App\Models\AccessKeyScope;
use Orion\Tests\Fixtures\App\Models\Company;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class HasManyRelationStandardUpdateOperationsTest extends TestCase
{
    /** @test */
    public function updating_a_single_relation_resource_without_authorization(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);
        $payload = ['name' => 'test updated'];

        Gate::policy(Team::class, RedPolicy::class);

        $response = $this->patch("/api/companies/{$company->id}/teams/{$team->id}", $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function updating_a_single_relation_resource_when_authorized(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);
        $payload = ['name' => 'test updated'];

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->patch("/api/companies/{$company->id}/teams/{$team->id}", $payload);

        $this->assertResourceUpdated($response,
            Team::class,
            $team->toArray(),
            $payload
        );
    }

    /** @test */
    public function updating_a_single_relation_resource_with_custom_key(): void
    {
        $accessKey = factory(AccessKey::class)->create();
        $accessKeyScope = factory(AccessKeyScope::class)->create(['access_key_id' => $accessKey->id]);
        $payload = ['scope' => 'test updated'];

        Gate::policy(AccessKeyScope::class, GreenPolicy::class);

        $response = $this->patch("/api/access_keys/{$accessKey->key}/scopes/{$accessKeyScope->scope}", $payload);

        $this->assertResourceUpdated($response,
            AccessKeyScope::class,
            $accessKeyScope->toArray(),
            $payload
        );
    }

    /** @test */
    public function updating_a_single_relation_resource_with_only_fillable_fields(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);
        $payload = ['name' => 'test updated', 'active' => false];

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->patch("/api/companies/{$company->id}/teams/{$team->id}", $payload);

        $this->assertResourceUpdated($response,
            Team::class,
            $team->toArray(),
            ['name' => 'test updated']
        );
        $this->assertDatabaseMissing('teams', ['active' => false]);
        $response->assertJsonMissing(['active' => false]);
    }

    /** @test */
    public function updating_a_single_relation_resource_when_validation_fails(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);
        $payload = ['name' => 'test updated', 'description' => 5];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(TeamRequest::class);

            return $componentsResolverMock;
        });

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->patch("/api/companies/{$company->id}/teams/{$team->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['description']]);
        $this->assertDatabaseMissing('teams', ['description' => 5]);
    }

    /** @test */
    public function transforming_a_single_updated_relation_resource(): void
    {
        $company = factory(Company::class)->create();
        $team = factory(Team::class)->create(['company_id' => $company->id]);
        $payload = ['name' => 'test updated'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->patch("/api/companies/{$company->id}/teams/{$team->id}", $payload);

        $this->assertResourceUpdated($response,
            Team::class,
            $team->toArray(),
            $payload,
            ['test-field-from-resource' => 'test-value']
        );
    }

    /** @test */
    public function updating_a_single_resource_and_getting_included_relation(): void
    {
        $company = factory(Company::class)->create()->fresh();
        $team = factory(Team::class)->create(['company_id' => $company->id]);
        $payload = ['name' => 'test updated'];

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->patch("/api/companies/{$company->id}/teams/{$team->id}?include=company", $payload);

        $this->assertResourceUpdated($response,
            Team::class,
            $team->toArray(),
            $payload,
            ['company' => $company->toArray()]
        );
    }
}