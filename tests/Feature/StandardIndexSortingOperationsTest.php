<?php

declare(strict_types=1);

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StandardIndexSortingOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_resources_asc_sorted_with_a_valid_sort_query_parameter(): void
    {
        $postC = factory(Post::class)->create(['title' => 'C'])->fresh();
        $postB = factory(Post::class)->create(['title' => 'B'])->fresh();
        $postA = factory(Post::class)->create(['title' => 'A'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'sort' => [
                    ['field' => 'title', 'direction' => 'asc'],
                ],
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$postA, $postB, $postC], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_asc_sorted_with_sort_query_parameter_missing_direction(): void
    {
        $postC = factory(Post::class)->create(['title' => 'C'])->fresh();
        $postB = factory(Post::class)->create(['title' => 'B'])->fresh();
        $postA = factory(Post::class)->create(['title' => 'A'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'sort' => [
                    ['field' => 'title'],
                ],
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$postA, $postB, $postC], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_desc_sorted_with_a_valid_sort_query_parameter(): void
    {
        $postA = factory(Post::class)->create(['title' => 'A'])->fresh();
        $postB = factory(Post::class)->create(['title' => 'B'])->fresh();
        $postC = factory(Post::class)->create(['title' => 'C'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'sort' => [
                    ['field' => 'title', 'direction' => 'desc'],
                ],
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$postC, $postB, $postA], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_sorted_by_not_whitelisted_field(): void
    {
        factory(Post::class)->create(['body' => 'C'])->fresh();
        factory(Post::class)->create(['body' => 'B'])->fresh();
        factory(Post::class)->create(['body' => 'A'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'sort' => [
                    ['field' => 'body', 'direction' => 'asc'],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['sort.0.field']]);
    }

    /** @test */
    public function getting_a_list_of_resources_asc_sorted_with_sort_query_parameter_missing_value(): void
    {
        $postA = factory(Post::class)->create(['title' => 'A'])->fresh();
        $postB = factory(Post::class)->create(['title' => 'B'])->fresh();
        $postC = factory(Post::class)->create(['title' => 'C'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'sort' => [],
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$postA, $postB, $postC], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_desc_sorted_by_field_in_json_column(): void
    {
        $postA = factory(Post::class)->create(['meta' => ['nested_field' => 'A']])->fresh();
        $postB = factory(Post::class)->create(['meta' => ['nested_field' => 'B']])->fresh();
        $postC = factory(Post::class)->create(['meta' => ['nested_field' => 'C']])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'sort' => [
                    ['field' => 'meta->nested_field', 'direction' => 'desc'],
                ],
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$postC, $postB, $postA], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_asc_sorted_by_relation_field(): void
    {
        $postC = factory(Post::class)->create(['user_id' => factory(User::class)->create(['name' => 'C'])->id])->fresh();
        $postB = factory(Post::class)->create(['user_id' => factory(User::class)->create(['name' => 'B'])->id])->fresh();
        $postA = factory(Post::class)->create(['user_id' => factory(User::class)->create(['name' => 'A'])->id])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'sort' => [
                    ['field' => 'user.name', 'direction' => 'asc'],
                ],
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$postA, $postB, $postC], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_asc_sorted_by_multiple_relation_fields(): void
    {
        $postC = factory(Post::class)->create(['user_id' => factory(User::class)->create(['name' => 'C'])->id])->fresh();
        $postB = factory(Post::class)->create(['user_id' => factory(User::class)->create(['name' => 'B'])->id])->fresh();
        $postA = factory(Post::class)->create(['user_id' => factory(User::class)->create(['name' => 'A'])->id])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'sort' => [
                    ['field' => 'user.name', 'direction' => 'asc'],
                    ['field' => 'user.email', 'direction' => 'asc'],
                ],
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$postA, $postB, $postC], 'posts/search')
        );
    }
}
