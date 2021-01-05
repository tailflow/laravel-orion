<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StandardIndexLimitingOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_limited_list_of_resources_with_a_valid_limit_query_parameter(): void
    {
        $posts = factory(Post::class)->times(15)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts?limit=5');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts', 1, 5)
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_limit_query_parameter_being_a_string(): void
    {
        $posts = factory(Post::class)->times(5)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts?limit=is+a+string');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_limit_query_parameter_being_a_negative_number(): void
    {
        $posts = factory(Post::class)->times(5)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts?limit=-1');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_limit_query_parameter_being_zero(): void
    {
        $posts = factory(Post::class)->times(5)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts?limit=0');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_limit_query_parameter_missing_value(): void
    {
        $posts = factory(Post::class)->times(5)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts?limit=');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }
}
