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

class BelongsToManyRelationStandardRestoreOperationsTest extends TestCase
{
    /** @test */
    public function restoring_a_single_relation_resource_without_authorization(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->create();
        $user->notifications()->save($trashedNotification);

        Gate::policy(Notification::class, RedPolicy::class);

        $response = $this->post("/api/users/{$user->id}/notifications/{$trashedNotification->id}/restore");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function restoring_a_single_relation_resource_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->create();
        $user->notifications()->save($trashedNotification);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/notifications/{$trashedNotification->id}/restore");

        $this->assertResourceRestored($response, $trashedNotification, [
            'pivot' => $user->notifications()->withTrashed()->first()->pivot->toArray()
        ]);
    }

    /** @test */
    public function restoring_a_single_relation_resource_with_casted_to_json_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->create();
        $user->notifications()->save($trashedNotification, [
            'meta' => json_encode(['key' => 'value'])
        ]);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/notifications/{$trashedNotification->id}/restore");

        $notification = $user->notifications()->withTrashed()->first();
        $notification->pivot->meta = ['key' => 'value'];

        $this->assertResourceRestored($response, $trashedNotification, [
            'pivot' => $notification->pivot->toArray()
        ]);
    }

    /** @test */
    public function restoring_a_single_not_trashed_relation_resource(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $notification = factory(Notification::class)->create();
        $user->notifications()->save($notification);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/notifications/{$notification->id}/restore");

        $this->assertResourceRestored($response, $notification, [
            'pivot' => $user->notifications()->withTrashed()->first()->pivot->toArray()
        ]);
    }

    /** @test */
    public function restoring_a_single_relation_resource_that_is_not_marked_as_soft_deletable(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->save($role);

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/roles/{$role->id}/restore");

        $response->assertNotFound();
    }

    /** @test */
    public function transforming_a_single_restored_relation_resource(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->create();
        $user->notifications()->save($trashedNotification);

        Gate::policy(Notification::class, GreenPolicy::class);

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->post("/api/users/{$user->id}/notifications/{$trashedNotification->id}/restore");

        $this->assertResourceRestored($response, $trashedNotification, [
            'pivot' => $user->notifications()->withTrashed()->first()->pivot->toArray(),
            'test-field-from-resource' => 'test-value'
        ]);
    }

    /** @test */
    public function restoring_a_single_relation_resource_and_getting_included_relation(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $trashedNotification = factory(Notification::class)->state('trashed')->create();
        $user->notifications()->save($trashedNotification);

        Gate::policy(Notification::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/notifications/{$trashedNotification->id}/restore?include=users");

        $notification = $user->notifications()->with('users')->first();

        $this->assertResourceRestored($response, $trashedNotification, [
            'pivot' => $notification->pivot->toArray(),
            'users' => $notification->users->toArray()
        ]);
    }
}