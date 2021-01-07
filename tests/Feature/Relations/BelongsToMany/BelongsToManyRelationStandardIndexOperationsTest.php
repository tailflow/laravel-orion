<?php

namespace Orion\Tests\Feature\Relations\BelongsToMany;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Resources\SampleCollectionResource;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Notification;
use Orion\Tests\Fixtures\App\Models\Role;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class BelongsToManyRelationStandardIndexOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_relation_resources_without_authorization(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->make();
        $user->roles()->saveMany($roles);

        Gate::policy(Role::class, RedPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function getting_a_list_of_relation_resources_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->make();
        $user->roles()->saveMany($roles);

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($user->roles->toArray(), "users/{$user->id}/roles")
        );
    }

    /** @test */
    public function getting_a_list_of_relation_resources_with_casted_to_json_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->make();
        $user->roles()->saveMany($roles,
            $roles->map(function () {
                return [
                    'meta' => json_encode(['key' => 'value'])
                ];
            })->toArray()
        );

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($user->load('roles')->roles->map(function(Role $role) {
                $role->pivot->meta = ['key' => 'value'];
                return $role;
            })->toArray(), "users/{$user->id}/roles")
        );
    }

    /** @test */
    public function getting_a_paginated_list_of_relation_resources(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(45)->make();
        $user->roles()->saveMany($roles);

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles?page=2");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($user->roles->toArray(), "users/{$user->id}/roles", 2)
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_relation_resources_when_with_trashed_query_parameter_is_present(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotifications = factory(Notification::class)->times(5)->state('trashed')->make();
        $notifications = factory(Notification::class)->times(5)->make();
        $user->notifications()->saveMany($trashedNotifications);
        $user->notifications()->saveMany($notifications);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/notifications?with_trashed=true");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($user->notifications()->withTrashed()->get()->toArray(), "users/{$user->id}/notifications")
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_relation_resources_when_only_trashed_query_parameter_is_present(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotifications = factory(Notification::class)->times(5)->state('trashed')->make();
        $notifications = factory(Notification::class)->times(5)->make();
        $user->notifications()->saveMany($trashedNotifications);
        $user->notifications()->saveMany($notifications);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/notifications?only_trashed=true");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($user->notifications()->onlyTrashed()->get()->toArray(), "users/{$user->id}/notifications")
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_relation_resources_with_trashed_resources_filtered_out(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotifications = factory(Notification::class)->times(5)->state('trashed')->make();
        $notifications = factory(Notification::class)->times(5)->make();
        $user->notifications()->saveMany($trashedNotifications);
        $user->notifications()->saveMany($notifications);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/notifications");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($user->notifications->toArray(), "users/{$user->id}/notifications")
        );
        $response->assertJsonMissing([
            'data' => $user->notifications()->onlyTrashed()->get()->toArray()
        ]);
    }

    /** @test */
    public function transforming_a_list_of_relation_resources(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(15)->make();
        $user->roles()->saveMany($roles);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($user->roles->toArray(), "users/{$user->id}/roles"),
            ['test-field-from-resource' => 'test-value']
        );
    }

    /** @test */
    public function transforming_a_list_of_relation_resources_using_collection_resource(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(15)->make();
        $user->roles()->saveMany($roles);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveCollectionResourceClass')->once()->andReturn(SampleCollectionResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($user->roles->toArray(), "users/{$user->id}/roles"),
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
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(15)->make();
        $user->roles()->saveMany($roles);

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles?include=users");

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($user->roles()->with('users')->get()->toArray(), "users/{$user->id}/roles")
        );
    }
}
