<?php

namespace Orion\Tests\Feature\Relations\BelongsToMany;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Requests\RoleRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Role;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class BelongsToManyRelationStandardStoreOperationsTest extends TestCase
{
    /** @test */
    public function storing_a_single_relation_resource_without_authorization(): void
    {
        $user = factory(User::class)->create();
        $payload = ['name' => 'test stored'];

        Gate::policy(Role::class, RedPolicy::class);

        $response = $this->post("/api/users/{$user->id}/roles", $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function storing_a_single_relation_resource_when_authorized(): void
    {
        $user = factory(User::class)->create();
        $payload = ['name' => 'test stored'];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/roles", $payload);

        $role = $user->roles()->first();

        $this->assertResourceStored($response,
            Role::class,
            $payload,
            ['pivot' => $role->pivot->toArray()]
        );
        $this->assertResourceAttached('roles', $user, $role);
    }

    /** @test */
    public function storing_a_single_relation_resource_with_casted_to_json_pivot_field(): void
    {
        $user = factory(User::class)->create();
        $payload = [
            'name' => 'test stored',
            'pivot' => [
                'references' => ['key' => 'value']
            ]
        ];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/roles", $payload);

        $role = $user->roles()->first();
        $role->pivot->references = ['key' => 'value'];

        $this->assertResourceStored($response,
            Role::class,
            $payload,
            ['pivot' => $role->pivot->toArray()]
        );
        $this->assertResourceAttached('roles', $user, $role, [
            'references' =>  ['key' => 'value']
        ]);
    }

    /** @test */
    public function storing_a_single_relation_resource_with_only_fillable_fields(): void
    {
        $user = factory(User::class)->create();
        $payload = ['name' => 'test stored', 'deprecated' => true];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/roles", $payload);

        $this->assertResourceStored($response,
            Role::class,
            ['name' => 'test stored'],
            ['pivot' => $user->roles()->first()->pivot->toArray()]
        );
        $this->assertDatabaseMissing('roles', ['deprecated' => true]);
        $response->assertJsonMissing(['deprecated' => true]);
    }

    /** @test */
    public function storing_a_single_relation_resource_with_only_fillable_pivot_fields(): void
    {
        $user = factory(User::class)->create();
        $payload = [
            'name' => 'test stored',
            'pivot' => [
                'meta' => ['key' => 'value'],
                'custom_name' => 'test custom'
            ]
        ];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/roles", $payload);

        $role = $user->roles()->first();

        $this->assertResourceStored($response,
            Role::class,
            ['name' => 'test stored'],
            ['pivot' => $role->pivot->toArray()]
        );
        $this->assertDatabaseHas('role_user', ['meta' => null]);
        $response->assertJsonMissing([
            'pivot' => [
                'meta' => ['key' => 'value']
            ]
        ]);
        $this->assertResourceAttached('roles', $user, $role, [
            'custom_name' => 'test custom',
            'meta' => null
        ]);
    }

    /** @test */
    public function storing_a_single_relation_resource_when_validation_fails_for_pivot_field(): void
    {
        $user = factory(User::class)->create();
        $payload = [
            'name' => 'test stored',
            'pivot' => [
                'custom_name' => 'test'
            ]
        ];

        Gate::policy(Role::class, GreenPolicy::class);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(RoleRequest::class);

            return $componentsResolverMock;
        });

        $response = $this->post("/api/users/{$user->id}/roles", $payload);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('role_user', ['custom_name' => 'test']);
        $response->assertJsonMissing([
            'pivot' => [
                'custom_name' => 'test'
            ]
        ]);
    }

    /** @test */
    public function storing_a_single_relation_resource_when_validation_fails(): void
    {
        $user = factory(User::class)->create();
        $payload = ['description' => 'abc'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(RoleRequest::class);

            return $componentsResolverMock;
        });

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/roles", $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['description']]);
        $this->assertDatabaseMissing('roles', ['description' => 'abc']);
    }

    /** @test */
    public function transforming_a_single_stored_relation_resource(): void
    {
        $user = factory(User::class)->create();
        $payload = ['name' => 'test stored'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/roles", $payload);

        $this->assertResourceStored(
            $response,
            Role::class,
            $payload,
            [
                'pivot' => $user->roles()->first()->pivot->toArray(),
                'test-field-from-resource' => 'test-value'
            ]
        );
    }

    /** @test */
    public function storing_a_single_relation_resource_and_getting_included_relation(): void
    {
        $user = factory(User::class)->create();
        $payload = ['name' => 'test stored'];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/roles?include=users", $payload);

        $role = $user->roles()->with('users')->first();

        $this->assertResourceStored($response, Role::class, $payload, [
            'pivot' => $role->pivot->toArray(),
            'users' => $role->users->toArray()
        ]);
    }
}