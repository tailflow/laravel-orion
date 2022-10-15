<?php

namespace Orion\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\Company;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StandardIncludeOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_resources_with_include_operation(): void
    {
        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 3, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'include' => [
                    ['relation' => 'posts']
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->load(['posts'])->toArray()], 'users/search'),
            [],
            false
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_include_operation_with_filters(): void
    {
        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 3, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'include' => [
                    [
                        'relation' => 'posts',
                        'filters' => [
                            ['field' => 'posts.stars', 'operator' => '>', 'value' => 3]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->load(['posts' => function($query) {$query->where('stars', '>', 3);}])->toArray()], 'users/search'),
            [],
            false
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_unauthorized_include(): void
    {
        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 1])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'include' => [
                    ['relation' => 'unauthorized']
                ]
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /** @test */
    public function getting_a_list_of_resources_with_unauthorized_include_filter(): void
    {
        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'include' => [
                    [
                        'relation' => 'posts',
                        'filters' => [
                            ['field' => 'unauthorized', 'operator' => '>', 'value' => 3]
                        ]
                    ]
                ]
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }
}
