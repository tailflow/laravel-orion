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

class BelongsToManyRelationStandardUpdateOperationsTest extends TestCase
{
    /** @test */
    public function updating_a_single_relation_resource_without_authorization(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);
        $payload = ['name' => 'test updated'];

        Gate::policy(Role::class, RedPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}", $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function updating_a_single_relation_resource_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);
        $payload = ['name' => 'test updated'];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}", $payload);

        $this->assertResourceUpdated(
            $response,
            Role::class,
            $role->toArray(),
            $payload,
            ['pivot' => $user->roles()->first()->pivot->toArray()]
        );
        $this->assertResourceAttached('roles', $user, $role);
    }

    /** @test */
    public function updating_a_single_relation_resource_with_casted_to_json_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);
        $payload = [
            'name' => 'test updated',
            'pivot' => [
                'references' => ['key' => 'value']
            ]
        ];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}", $payload);

        $updatedRole = $user->roles()->first();
        $updatedRole->pivot->references = ['key' => 'value'];

        $this->assertResourceUpdated(
            $response,
            Role::class,
            $role->toArray(),
            $payload,
            ['pivot' => $updatedRole->pivot->toArray()]
        );
        $this->assertResourceAttached('roles', $user, $role, [
            'references' => ['key' => 'value']
        ]);
    }

    /** @test */
    public function updating_a_single_relation_resource_with_only_fillable_fields(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);
        $payload = ['name' => 'test updated', 'deprecated' => true];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}", $payload);

        $this->assertResourceUpdated($response,
            Role::class,
            $role->toArray(),
            ['name' => 'test updated'],
            ['pivot' => $user->roles()->first()->pivot->toArray()]
        );
        $this->assertDatabaseMissing('roles', ['deprecated' => true]);
        $response->assertJsonMissing(['deprecated' => true]);
    }

    /** @test */
    public function updating_a_single_relation_resource_with_only_fillable_pivot_fields(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);
        $payload = [
            'name' => 'test updated',
            'pivot' => [
                'meta' => ['key' => 'value'],
                'custom_name' => 'test custom'
            ]
        ];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}", $payload);

        $this->assertResourceUpdated($response,
            Role::class,
            $role->toArray(),
            ['name' => 'test updated'],
            ['pivot' => $user->roles()->first()->pivot->toArray()]
        );
        $this->assertDatabaseHas('role_user', ['meta' => null]);
        $response->assertJsonMissing([
            'pivot' => [
                'meta' => ['key' => 'value']
            ]
        ]);
        $this->assertResourceAttached('roles', $user, $role, [
            'meta' => null,
            'custom_name' => 'test custom'
        ]);
    }

    /** @test */
    public function updating_a_single_relation_resource_when_validation_fails_for_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);
        $payload = [
            'name' => 'test stored',
            'pivot' => [
                'custom_name' => 'test'
            ]
        ];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(RoleRequest::class);

            return $componentsResolverMock;
        });

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}", $payload);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('role_user', ['custom_name' => 'test']);
        $response->assertJsonMissing([
            'pivot' => [
                'custom_name' => 'test'
            ]
        ]);
    }

    /** @test */
    public function updating_a_single_relation_resource_when_validation_fails(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);
        $payload = ['description' => 'a'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(RoleRequest::class);

            return $componentsResolverMock;
        });

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['description']]);
        $this->assertDatabaseMissing('roles', ['description' => 'a']);
    }

    /** @test */
    public function transforming_a_single_updated_relation_resource(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);
        $payload = ['name' => 'test updated'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}", $payload);

        $this->assertResourceUpdated($response,
            Role::class,
            $role->toArray(),
            $payload,
            [
                'pivot' => $user->roles()->first()->pivot->toArray(),
                'test-field-from-resource' => 'test-value'
            ]
        );
    }

    /** @test */
    public function updating_a_single_resource_and_getting_included_relation(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);
        $payload = ['name' => 'test updated'];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}?include=users", $payload);

        $updatedRole = $user->roles()->with('users')->first();

        $this->assertResourceUpdated($response,
            Role::class,
            $role->toArray(),
            $payload,
            [
                'pivot' => $updatedRole->pivot->toArray(),
                'users' => $updatedRole->users->toArray()
            ]
        );
    }
}