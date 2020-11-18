<?php

declare(strict_types=1);

namespace Orion\Tests\Feature\Relations\BelongsToMany;

use Illuminate\Support\Facades\Gate;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Models\Role;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class BelongsToManyRelationManyToManyOperationsTest extends TestCase
{
    /** @test */
    public function attaching_relation_resources_when_unauthorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->create();

        Gate::policy(User::class, RedPolicy::class);

        self::assertEquals(0, $user->roles()->count());

        $response = $this->post("/api/users/{$user->id}/roles/attach", [
            'resources' => $roles->pluck('id')->toArray()
        ]);

        $this->assertUnauthorizedResponse($response);
        self::assertEquals(0, $user->roles()->count());
    }

    /** @test */
    public function attaching_relation_resources_when_authorized_only_on_parent(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->create();

        Gate::policy(User::class, GreenPolicy::class);

        self::assertEquals(0, $user->roles()->count());


        $response = $this->post("/api/users/{$user->id}/roles/attach", [
            'resources' => $roles->pluck('id')->toArray()
        ]);

        $this->assertNoResourcesAttached($response, 'roles', $user);
    }

    /** @test */
    public function attaching_relation_resources_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleA = factory(Role::class)->create();
        $roleB = factory(Role::class)->create();

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(0, $user->roles()->count());

        $response = $this->post("/api/users/{$user->id}/roles/attach", [
            'resources' => [$roleA->id, $roleB->id]
        ]);

        $this->assertResourcesAttached(
            $response,
            'roles',
            $user,
            collect([$roleA, $roleB])
        );
    }

    /** @test */
    public function attaching_relation_resources_without_duplicates(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleA = factory(Role::class)->create();
        $user->roles()->attach($roleA->id);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(1, $user->roles()->count());

        $response = $this->post("/api/users/{$user->id}/roles/attach", [
            'resources' => [$roleA->id],
            'duplicates' => false
        ]);

        $this->assertResourcesAttached(
            $response,
            'roles',
            $user,
            collect([])
        );
        self::assertEquals(1, $user->roles()->count());
    }

    /** @test */
    public function attaching_relation_resources_with_duplicates(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleA = factory(Role::class)->create();
        $user->roles()->attach($roleA->id);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(1, $user->roles()->count());

        $response = $this->post("/api/users/{$user->id}/roles/attach", [
            'resources' => [$roleA->id],
            'duplicates' => true
        ]);

        $this->assertResourcesAttached(
            $response,
            'roles',
            $user,
            collect([$roleA])
        );
        self::assertEquals(2, $user->roles()->count());
    }

    /** @test */
    public function attaching_relation_resources_with_only_fillable_pivot_fields(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleA = factory(Role::class)->create();
        $roleB = factory(Role::class)->create();

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(0, $user->roles()->count());

        $response = $this->post("/api/users/{$user->id}/roles/attach", [
            'resources' => [
                $roleA->id => [
                    'meta' => ['key1' => 'item1'],
                    'custom_name' => 'test value 1'
                ],
                $roleB->id => [
                    'meta' => ['key2' => 'item2'],
                    'custom_name' => 'test value 2'
                ],
            ]
        ]);

        $this->assertResourcesAttached(
            $response,
            'roles',
            $user,
            collect([$roleA, $roleB]),
            [
                $roleA->id => [
                    'meta' => null,
                    'custom_name' => 'test value 1'
                ],
                $roleB->id => [
                    'meta' => null,
                    'custom_name' => 'test value 2'
                ]
            ]
        );
    }

    /** @test */
    public function attaching_relation_resources_with_casted_to_json_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleA = factory(Role::class)->create();
        $roleB = factory(Role::class)->create();

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(0, $user->roles()->count());

        $response = $this->post("/api/users/{$user->id}/roles/attach", [
            'resources' => [
                $roleA->id => ['references' => ['key1' => 'item1']],
                $roleB->id => ['references' => ['key2' => 'item2']],
            ]
        ]);

        $this->assertResourcesAttached(
            $response,
            'roles',
            $user,
            collect([$roleA, $roleB]),
            [
                $roleA->id => ['references' => ['key1' => 'item1']],
                $roleB->id => ['references' => ['key2' => 'item2']]
            ]
        );
    }

    /** @test */
    public function detaching_relation_resources_when_unauthorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->make();
        $user->roles()->saveMany($roles);

        Gate::policy(User::class, RedPolicy::class);

        self::assertEquals(5, $user->roles()->count());

        $response = $this->delete("/api/users/{$user->id}/roles/detach", [
            'resources' => $user->roles()->get()->pluck('id')->toArray()
        ]);

        $this->assertUnauthorizedResponse($response);
        self::assertEquals(5, $user->roles()->count());
    }

    /** @test */
    public function detaching_relation_resources_when_authorized_only_on_parent(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->make();
        $user->roles()->saveMany($roles);

        Gate::policy(User::class, GreenPolicy::class);

        self::assertEquals(5, $user->roles()->count());

        $response = $this->delete("/api/users/{$user->id}/roles/detach", [
            'resources' => $user->roles()->get()->pluck('id')->toArray()
        ]);

        $this->assertNoResourcesDetached($response, 'roles', $user, 5);
    }

    /** @test */
    public function detaching_relation_resources_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->make();
        $user->roles()->saveMany($roles);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        $roles = $user->roles()->get();

        self::assertEquals(5, $roles->count());

        $response = $this->delete("/api/users/{$user->id}/roles/detach", [
            'resources' => $roles->pluck('id')->toArray()
        ]);

        $this->assertResourcesDetached($response, 'roles', $user, $roles);
    }

    /** @test */
    public function syncing_relation_resources_when_unauthorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->create();

        Gate::policy(User::class, RedPolicy::class);

        self::assertEquals(0, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/sync", [
            'resources' => $roles->pluck('id')->toArray()
        ]);

        $this->assertUnauthorizedResponse($response);
        self::assertEquals(0, $user->roles()->count());
    }

    /** @test */
    public function syncing_relation_resources_when_authorized_only_on_parent(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->create();

        Gate::policy(User::class, GreenPolicy::class);

        self::assertEquals(0, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/sync", [
            'resources' => $roles->pluck('id')->toArray()
        ]);

        $this->assertNoResourcesSynced($response, 'roles', $user);
    }

    /** @test */
    public function syncing_relation_resources_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleToAttach = factory(Role::class)->create();
        $roleToDetach = factory(Role::class)->create();
        $roleToUpdate = factory(Role::class)->create();
        $user->roles()->attach($roleToDetach->id);
        $user->roles()->attach($roleToUpdate->id, ['custom_name' => 'test original']);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(2, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/sync", [
            'resources' => [
                $roleToAttach->id,
                $roleToUpdate->id => ['custom_name' => 'test updated']
            ]
        ]);

        $this->assertResourcesSynced(
            $response,
            'roles',
            $user,
            $this->buildSyncMap([$roleToAttach], [$roleToDetach], [$roleToUpdate]),
            [$roleToUpdate->id => ['custom_name' => 'test updated']]
        );
    }

    /** @test */
    public function syncing_relation_resources_with_only_fillable_pivot_fields(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleToAttach = factory(Role::class)->create();
        $roleToDetach = factory(Role::class)->create();
        $roleToUpdate = factory(Role::class)->create();
        $user->roles()->attach($roleToDetach->id);
        $user->roles()->attach($roleToUpdate->id);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(2, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/sync", [
            'resources' => [
                $roleToAttach->id => [
                    'meta' => ['key1' => 'value1'],
                    'custom_name' => 'test value 1'
                ],
                $roleToUpdate->id => [
                    'meta' => ['key2' => 'value2'],
                    'custom_name' => 'test value 2'
                ]
            ]
        ]);

        $this->assertResourcesSynced(
            $response,
            'roles',
            $user,
            $this->buildSyncMap([$roleToAttach], [$roleToDetach], [$roleToUpdate]),
            [
                $roleToAttach->id => [
                    'meta' => null,
                    'custom_name' => 'test value 1'
                ],
                $roleToUpdate->id => [
                    'meta' => null,
                    'custom_name' => 'test value 2'
                ]
            ]
        );
    }

    /** @test */
    public function syncing_relation_resources_with_casted_to_json_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleToAttach = factory(Role::class)->create();
        $roleToDetach = factory(Role::class)->create();
        $roleToUpdate = factory(Role::class)->create();
        $user->roles()->attach($roleToDetach->id);
        $user->roles()->attach($roleToUpdate->id, ['references' => json_encode(['key2' => 'value2'])]);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(2, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/sync", [
            'resources' => [
                $roleToAttach->id => ['references' => ['key1' => 'value1']],
                $roleToUpdate->id => ['references' => ['key2' => 'value updated']]
            ]
        ]);

        $this->assertResourcesSynced(
            $response,
            'roles',
            $user,
            $this->buildSyncMap([$roleToAttach], [$roleToDetach], [$roleToUpdate]),
            [
                $roleToAttach->id => ['references' => ['key1' => 'value1']],
                $roleToUpdate->id => ['references' => ['key2' => 'value updated']]
            ]
        );
    }

    /** @test */
    public function syncing_relation_resources_without_detaching(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleToAttach = factory(Role::class)->create();
        $roleToRemain = factory(Role::class)->create();
        $user->roles()->attach($roleToRemain->id);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(1, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/sync", [
            'resources' => [$roleToAttach->id],
            'detaching' => false
        ]);

        $this->assertResourcesSynced(
            $response,
            'roles',
            $user,
            $this->buildSyncMap([$roleToAttach], [], [], [$roleToRemain])
        );
    }

    /** @test */
    public function toggling_relation_resources_when_unauthorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->create();
        $user->roles()->saveMany($roles);

        Gate::policy(User::class, RedPolicy::class);

        self::assertEquals(5, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/toggle", [
            'resources' => $roles->pluck('id')->toArray()
        ]);

        $this->assertUnauthorizedResponse($response);
        self::assertEquals(5, $user->roles()->count());
    }

    /** @test */
    public function toggling_relation_resources_when_authorized_only_on_parent(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class)->times(5)->create();
        $user->roles()->saveMany($roles);

        Gate::policy(User::class, GreenPolicy::class);

        self::assertEquals(5, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/toggle", [
            'resources' => $roles->pluck('id')->toArray()
        ]);

        $this->assertNoResourcesToggled($response, 'roles', $user, 5);
    }

    /** @test */
    public function toggling_relation_resources_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleToAttach = factory(Role::class)->create();
        $roleToDetach = factory(Role::class)->create();
        $user->roles()->attach($roleToDetach->id);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(1, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/toggle", [
            'resources' => [$roleToAttach->id, $roleToDetach->id]
        ]);

        $this->assertResourcesToggled(
            $response,
            'roles',
            $user,
            $this->buildSyncMap([$roleToAttach], [$roleToDetach])
        );
    }

    /** @test */
    public function toggling_relation_resources_with_only_fillable_pivot_fields(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleToAttach = factory(Role::class)->create();
        $roleToDetach = factory(Role::class)->create();
        $user->roles()->attach($roleToDetach->id);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(1, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/toggle", [
            'resources' => [
                $roleToAttach->id => [
                    'meta' => ['key' => 'item'],
                    'custom_name' => 'test value'
                ],
                $roleToDetach->id
            ]
        ]);

        $this->assertResourcesToggled(
            $response,
            'roles',
            $user,
            $this->buildSyncMap([$roleToAttach], [$roleToDetach]),
            [
                $roleToAttach->id => [
                    'meta' => null,
                    'custom_name' => 'test value'
                ]
            ]
        );
    }

    /** @test */
    public function toggling_relation_resources_with_casted_to_json_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roleToAttach = factory(Role::class)->create();
        $roleToDetach = factory(Role::class)->create();
        $user->roles()->attach($roleToDetach->id);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        self::assertEquals(1, $user->roles()->count());

        $response = $this->patch("/api/users/{$user->id}/roles/toggle", [
            'resources' => [
                $roleToAttach->id => [
                    'references' => ['key' => 'item']
                ],
                $roleToDetach->id
            ]
        ]);

        $this->assertResourcesToggled(
            $response,
            'roles',
            $user,
            $this->buildSyncMap([$roleToAttach], [$roleToDetach]),
            [
                $roleToAttach->id => [
                    'references' => ['key' => 'item']
                ]
            ]
        );
    }

    /** @test */
    public function updating_pivot_of_relation_resource_when_unauthorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->save($role);

        Gate::policy(Role::class, RedPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}/pivot", [
            'pivot' => [
                'custom_name' => 'test value'
            ]
        ]);

        $this->assertUnauthorizedResponse($response);
        $this->assertResourceAttached('roles', $user, $role, ['custom_name' => null]);
    }

    /** @test */
    public function updating_pivot_of_relation_resource_when_authorized(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->save($role);

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}/pivot", [
            'pivot' => [
                'custom_name' => 'test value'
            ]
        ]);

        $this->assertResourcePivotUpdated(
            $response,
            'roles',
            $user,
            $role,
            ['custom_name' => 'test value']
        );
    }

    /** @test */
    public function updating_pivot_of_relation_resource_with_only_fillable_pivot_fields(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->save($role);

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}/pivot", [
            'pivot' => [
                'custom_name' => 'test value',
                'meta' => ['key' => 'value']
            ]
        ]);

        $this->assertResourcePivotUpdated(
            $response,
            'roles',
            $user,
            $role,
            ['custom_name' => 'test value', 'meta' => null]
        );
    }

    /** @test */
    public function updating_pivot_of_relation_resource_casted_to_json_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $user->roles()->save($role);

        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->patch("/api/users/{$user->id}/roles/{$role->id}/pivot", [
            'pivot' => [
                'references' => ['key' => 'value']
            ]
        ]);

        $this->assertResourcePivotUpdated(
            $response,
            'roles',
            $user,
            $role,
            ['references' => ['key' => 'value']]
        );
    }
}