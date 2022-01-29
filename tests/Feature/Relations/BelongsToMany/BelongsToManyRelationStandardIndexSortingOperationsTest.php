<?php

declare(strict_types=1);

namespace Orion\Tests\Feature\Relations\BelongsToMany;

use Illuminate\Support\Facades\Gate;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Models\Role;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class BelongsToManyRelationStandardIndexSortingOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_relation_resources_desc_sorted_by_pivot_field(): void
    {
        if ((float) app()->version() <= 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        /** @var User $user */
        $user = factory(User::class)->create();

        $roleA = factory(Role::class)->create();
        $roleB = factory(Role::class)->create();
        $roleC = factory(Role::class)->create();

        $user->roles()->attach($roleA, ['custom_name' => 'a']);
        $user->roles()->attach($roleB, ['custom_name' => 'b']);
        $user->roles()->attach($roleC, ['custom_name' => 'c']);

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Role::class, GreenPolicy::class);

        $response = $this->withoutExceptionHandling()->post("/api/users/{$user->id}/roles/search", [
            'sort' => [
                ['field' => 'pivot.custom_name', 'direction' => 'desc']
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($user->roles()->get()->reverse()->toArray(), "users/{$user->id}/roles/search")
        );
    }
}
