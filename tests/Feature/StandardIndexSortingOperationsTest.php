<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StandardIndexSortingOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_resources_asc_sorted_with_a_valid_sort_query_parameter()
    {
        $postC = factory(Post::class)->create(['title' => 'C'])->fresh();
        $postB = factory(Post::class)->create(['title' => 'B'])->fresh();
        $postA = factory(Post::class)->create(['title' => 'A'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'sort' => [
                ['field' => 'title', 'direction' => 'asc']
            ]
        ]);

        $this->assertResourceListed(
            $response,
            $this->makePaginator([$postA, $postB, $postC], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_asc_sorted_with_sort_query_parameter_missing_direction()
    {
        $postC = factory(Post::class)->create(['title' => 'C'])->fresh();
        $postB = factory(Post::class)->create(['title' => 'B'])->fresh();
        $postA = factory(Post::class)->create(['title' => 'A'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'sort' => [
                ['field' => 'title']
            ]
        ]);

        $this->assertResourceListed(
            $response,
            $this->makePaginator([$postA, $postB, $postC], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_desc_sorted_with_a_valid_sort_query_parameter()
    {
        $postA = factory(Post::class)->create(['title' => 'A'])->fresh();
        $postB = factory(Post::class)->create(['title' => 'B'])->fresh();
        $postC = factory(Post::class)->create(['title' => 'C'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'sort' => [
                ['field' => 'title', 'direction' => 'desc']
            ]
        ]);

        $this->assertResourceListed(
            $response,
            $this->makePaginator([$postC, $postB, $postA], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_sorted_by_not_whitelisted_field()
    {
        factory(Post::class)->create(['body' => 'C'])->fresh();
        factory(Post::class)->create(['body' => 'B'])->fresh();
        factory(Post::class)->create(['body' => 'A'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'sort' => [
                ['field' => 'body', 'direction' => 'asc']
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['sort.0.field']]);
    }

    /** @test */
    public function getting_a_list_of_resources_asc_sorted_with_sort_query_parameter_missing_value()
    {
        $postA = factory(Post::class)->create(['title' => 'A'])->fresh();
        $postB = factory(Post::class)->create(['title' => 'B'])->fresh();
        $postC = factory(Post::class)->create(['title' => 'C'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'sort' => []
        ]);

        $this->assertResourceListed(
            $response,
            $this->makePaginator([$postA, $postB, $postC], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_asc_sorted_by_relation_field()
    {
        $postC = factory(Post::class)->create(['user_id' => factory(User::class)->create(['name' => 'C'])->id])->fresh();
        $postB = factory(Post::class)->create(['user_id' => factory(User::class)->create(['name' => 'B'])->id])->fresh();
        $postA = factory(Post::class)->create(['user_id' => factory(User::class)->create(['name' => 'A'])->id])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'sort' => [
                ['field' => 'user.name', 'direction' => 'asc']
            ]
        ]);

        $this->assertResourceListed(
            $response,
            $this->makePaginator([$postA, $postB, $postC], 'posts/search')
        );
    }
}
