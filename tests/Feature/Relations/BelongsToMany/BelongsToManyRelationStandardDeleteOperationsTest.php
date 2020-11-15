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

class BelongsToManyRelationStandardDeleteOperationsTest extends TestCase
{
    /** @test */
    public function trashing_a_single_soft_deletable_relation_resource_without_authorization(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $notification = factory(Notification::class)->create();
        $user->notifications()->save($notification);

        Gate::policy(Notification::class, RedPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/notifications/{$notification->id}");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function deleting_a_single_relation_resource_without_authorization(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->save($role);

        Gate::policy(Role::class, RedPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/roles/{$role->id}");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function force_deleting_a_single_relation_resource_without_authorization(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->create();
        $user->notifications()->save($trashedNotification);

        Gate::policy(Notification::class, RedPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/notifications/{$trashedNotification->id}?force=true");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function trashing_a_single_soft_deletable_relation_resource_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $notification = factory(Notification::class)->create();
        $user->notifications()->save($notification);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/notifications/{$notification->id}");

        $this->assertResourceTrashed($response, $notification, [
            'pivot' => $user->notifications()->withTrashed()->first()->pivot->toArray()
        ]);
    }

    /** @test */
    public function deleting_a_single_relation_resource_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->save($role);

        $role = $user->roles()->first();

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/roles/{$role->id}");

        $this->assertResourceDeleted($response, $role);
    }

    /** @test */
    public function deleting_a_single_relation_resource_with_casted_to_json_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->save($role, [
            'meta' => json_encode(['key' => 'value'])
        ]);

        $role = $user->roles()->first();
        $role->pivot->meta = ['key' => 'value'];

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/roles/{$role->id}");

        $this->assertResourceDeleted($response, $role);
    }

    /** @test */
    public function force_deleting_a_single_trashed_relation_resource_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->create();
        $user->notifications()->save($trashedNotification);

        $trashedNotification = $user->notifications()->withTrashed()->first();

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/notifications/{$trashedNotification->id}?force=true");

        $this->assertResourceDeleted($response, $trashedNotification);
    }

    /** @test */
    public function deleting_a_single_trashed_relation_resource_without_trashed_query_parameter(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->create();
        $user->notifications()->save($trashedNotification);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/notifications/{$trashedNotification->id}");

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('notifications', $trashedNotification->getAttributes());
    }

    /** @test */
    public function deleting_a_single_trashed_relation_resource_with_trashed_query_parameter(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->create();
        $user->notifications()->save($trashedNotification);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/notifications/{$trashedNotification->id}?with_trashed=true");

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('notifications', $trashedNotification->getAttributes());
    }

    /** @test */
    public function transforming_a_single_deleted_relation_resource(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->save($role);

        $role = $user->roles()->first();

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/roles/{$role->id}");

        $this->assertResourceDeleted($response, $role, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function deleting_a_single_relation_resource_and_getting_included_relation(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->save($role);

        $role = $user->roles()->with('users')->first();

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->delete("/api/users/{$user->id}/roles/{$role->id}?include=users");

        $this->assertResourceDeleted($response, $role);
    }
}