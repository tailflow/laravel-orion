<?php

declare(strict_types=1);

namespace Orion\Tests\Feature\Relations\BelongsToMany;

use Illuminate\Support\Facades\Gate;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Models\Role;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class BelongsToManyRelationStandardIndexFilteringOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_relation_resources_filtered_by_pivot_field(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $roleWithCustomName = factory(Role::class)->create();
        $roleWithoutCustomName = factory(Role::class)->create();

        $user->roles()->attach($roleWithCustomName, ['custom_name' => 'test-name']);
        $user->roles()->attach($roleWithoutCustomName);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->post("/api/users/{$user->id}/roles/search", [
            'filters' => [
                ['field' => 'pivot.custom_name', 'operator' => '=', 'value' => 'test-name'],
            ],
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->roles()->first()->toArray()], "users/{$user->id}/roles/search")
        );
    }

    /** @test */
    public function getting_a_list_of_relation_resources_filtered_by_null_pivot_field_value_using_equality_operator(
    ): void
    {
        if ((float) app()->version() <= 7.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        /** @var User $user */
        $user = factory(User::class)->create();

        $roleWithCustomName = factory(Role::class)->create();
        $roleWithoutCustomName = factory(Role::class)->create();

        $user->roles()->attach($roleWithoutCustomName);
        $user->roles()->attach($roleWithCustomName, ['custom_name' => 'test-name']);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        if ((float) app()->version() <= 7.0) {
            $this->expectExceptionMessage(
                "Filtering by nullable pivot fields is only supported for Laravel version > 8.0"
            );
        }

        $response = $this->withoutExceptionHandling()->post("/api/users/{$user->id}/roles/search", [
            'filters' => [
                ['field' => 'pivot.custom_name', 'operator' => '=', 'value' => null],
            ],
        ]);

        if ((float) app()->version() > 7.0) {
            $this->assertResourcesPaginated(
                $response,
                $this->makePaginator(
                    [$user->roles()->where('roles.id', $roleWithoutCustomName->id)->first()->toArray()],
                    "users/{$user->id}/roles/search"
                )
            );
        }
    }

    /** @test */
    public function getting_a_list_of_relation_resources_filtered_by_null_pivot_field_value_using_in_operator(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $roleWithCustomName = factory(Role::class)->create();
        $roleWithoutCustomName = factory(Role::class)->create();

        $user->roles()->attach($roleWithoutCustomName);
        $user->roles()->attach($roleWithCustomName, ['custom_name' => 'test-name']);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        if ((float) app()->version() <= 7.0) {
            $this->expectExceptionMessage(
                "Filtering by nullable pivot fields is only supported for Laravel version > 8.0"
            );
        }

        $response = $this->withoutExceptionHandling()->post("/api/users/{$user->id}/roles/search", [
            'filters' => [
                ['field' => 'pivot.custom_name', 'operator' => 'in', 'value' => [null]],
            ],
        ]);

        if ((float) app()->version() > 7.0) {
            $this->assertResourcesPaginated(
                $response,
                $this->makePaginator(
                    [$user->roles()->where('roles.id', $roleWithoutCustomName->id)->first()->toArray()],
                    "users/{$user->id}/roles/search"
                )
            );
        }
    }
}
