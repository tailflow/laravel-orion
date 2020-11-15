<?php

namespace Orion\Tests\Feature\Relations\BelongsToMany;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Notification;
use Orion\Tests\Fixtures\App\Models\Role;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class BelongsToManyRelationStandardShowOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_single_relation_resource_without_parent_authorization(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);

        Gate::policy(Role::class, RedPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles/{$role->id}");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function getting_a_single_relation_resource_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles/{$role->id}");

        $this->assertResourceShown($response, $user->roles->first()->toArray());
    }

    /** @test */
    public function getting_a_single_relation_resource_with_casted_to_json_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role, [
            'meta' => json_encode(['key' => 'value'])
        ]);

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles/{$role->id}");

        $role = $user->roles->first();
        $role->pivot->meta = ['key' => 'value'];
        $this->assertResourceShown($response, $role->toArray());
    }

    /** @test */
    public function getting_a_single_trashed_relation_resource_when_with_trashed_query_parameter_is_missing(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->make();
        $user->notifications()->save($trashedNotification);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/notifications/{$trashedNotification->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function getting_a_single_trashed_relation_resource_when_with_trashed_query_parameter_is_present(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->create();
        $user->notifications()->save($trashedNotification);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/notifications/{$trashedNotification->id}?with_trashed=true");

        $this->assertResourceShown($response, $user->notifications()->withTrashed()->first()->toArray());
    }


    /** @test */
    public function getting_a_single_transformed_relation_resource(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles/{$role->id}");

        $this->assertResourceShown($response, $user->roles->first()->toArray(), ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function getting_a_single_relation_resource_with_included_relation(): void
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->make();
        $user->roles()->save($role);

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->get("/api/users/{$user->id}/roles/{$role->id}?include=users");

        $this->assertResourceShown($response, $user->roles()->with('users')->first()->toArray());
    }
}