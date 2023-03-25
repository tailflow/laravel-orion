<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StandardAggregateOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_resources_with_avg_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 3, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    ['relation' => 'posts', 'field' => 'stars', 'type' => 'avg']
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadAvg('posts', 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_avg_aggregate_operation_with_filters(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    [
                        'relation' => 'posts',
                        'field' => 'stars',
                        'type' => 'avg',
                        'filters' => [
                            ['field' => 'posts.stars', 'operator' => '>', 'value' => 3]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadAvg(['posts' => function($query) {$query->where('stars', '>', 3);}], 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_min_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();
        factory(Post::class)->create(['stars' => 1])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    ['relation' => 'posts', 'field' => 'stars', 'type' => 'min']
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadMin('posts', 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_min_aggregate_operation_with_filters(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    [
                        'relation' => 'posts',
                        'field' => 'stars',
                        'type' => 'min',
                        'filters' => [
                            ['field' => 'posts.stars', 'operator' => '>', 'value' => 3]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadMin(['posts' => function($query) {$query->where('stars', '>', 3);}], 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function ensuring_root_level_filters_are_not_applied_on_aggregates(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();
        $user->name = 'John Doe';
        $user->save();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'filters' => [
                    ['field' => 'name', 'operator' => '=', 'value' => 'John Doe'],
                ],
                'aggregates' => [
                    [
                        'relation' => 'posts',
                        'field' => 'stars',
                        'type' => 'min',
                    ]
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadMin(['posts'], 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_max_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();
        factory(Post::class)->create(['stars' => 1])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    ['relation' => 'posts', 'field' => 'stars', 'type' => 'max']
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadMax('posts', 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_max_aggregate_operation_with_filters(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    [
                        'relation' => 'posts',
                        'field' => 'stars',
                        'type' => 'max',
                        'filters' => [
                            ['field' => 'posts.stars', 'operator' => '<', 'value' => 3]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadMax(['posts' => function($query) {$query->where('stars', '<', 3);}], 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_sum_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.5, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();
        factory(Post::class)->create(['stars' => 1])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    ['relation' => 'posts', 'field' => 'stars', 'type' => 'sum']
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadSum('posts', 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_sum_aggregate_operation_with_filters(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    [
                        'relation' => 'posts',
                        'field' => 'stars',
                        'type' => 'sum',
                        'filters' => [
                            ['field' => 'posts.stars', 'operator' => '>', 'value' => 3]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadSum(['posts' => function($query) {$query->where('stars', '>', 3);}], 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_count_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 1])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    ['relation' => 'posts', 'type' => 'count']
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadCount('posts')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_count_aggregate_operation_with_filters(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    [
                        'relation' => 'posts',
                        'type' => 'count',
                        'filters' => [
                            ['field' => 'posts.stars', 'operator' => '>', 'value' => 3]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadCount(['posts' => function($query) {$query->where('stars', '>', 3);}], 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_exists_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 1])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    ['relation' => 'posts', 'type' => 'exists']
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadExists('posts')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_exists_aggregate_operation_with_filters(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    [
                        'relation' => 'posts',
                        'type' => 'exists',
                        'filters' => [
                            ['field' => 'posts.stars', 'operator' => '>', 'value' => 3]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadExists(['posts' => function($query) {$query->where('stars', '>', 3);}])->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_exists_aggregate_operation_with_filters_and_no_results(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    [
                        'relation' => 'posts',
                        'type' => 'exists',
                        'filters' => [
                            ['field' => 'posts.stars', 'operator' => '>', 'value' => 99999]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadExists(['posts' => function($query) {$query->where('stars', '>', 999);}])->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_unknown_aggregate(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 1])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    ['relation' => 'posts', 'type' => 'unknown']
                ]
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['aggregates.0.type']]);
    }

    /** @test */
    public function getting_a_list_of_resources_with_unauthorized_relation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 1])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    ['relation' => 'unauthorized', 'type' => 'count']
                ]
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['aggregates.0.field']]);
    }

    /** @test */
    public function getting_a_list_of_resources_with_unauthorized_field(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 1])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    ['relation' => 'posts', 'field' => 'id', 'type' => 'avg']
                ]
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['aggregates.0.field']]);
    }

    /** @test */
    public function getting_a_list_of_resources_with_unauthorized_filter(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search',
            [
                'aggregates' => [
                    [
                        'relation' => 'posts',
                        'type' => 'exists',
                        'filters' => [
                            ['field' => 'posts.id', 'operator' => '>', 'value' => 3]
                        ]
                    ]
                ]
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['aggregates.0.filters.0.field']]);
    }

    /** @test */
    public function getting_a_list_of_resources_with_query_exists_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search?with_exists=posts'
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadExists(['posts'])->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_query_count_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search?with_count=posts'
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadCount(['posts'])->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_query_min_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search?with_min=posts.stars'
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadMin(['posts'], 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_query_max_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search?with_max=posts.stars'
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadMax(['posts'], 'stars')->toArray()], 'users/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_query_sum_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search?with_sum=posts.stars'
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadSum(['posts'], 'stars')->toArray()], 'users/search'),
            [],
            false
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_query_avg_aggregate_operation(): void
    {
        if ((float) app()->version() < 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $user = User::query()->first();

        factory(Post::class)->create(['stars' => 2.8, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 4.2, 'user_id' => $user->id])->fresh();
        factory(Post::class)->create(['stars' => 5])->fresh();

        Gate::policy(User::class, GreenPolicy::class);

        $response = $this->post(
            '/api/users/search?with_avg=posts.stars'
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$user->loadAvg(['posts'], 'stars')->toArray()], 'users/search')
        );
    }
}
