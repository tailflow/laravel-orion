<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Collection;
use Orion\Tests\Fixtures\App\Models\Post;

class StandardIndexLimitingOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_limited_list_of_resources_with_a_valid_limit_query_parameter()
    {
        /**
         * @var Collection $posts
         */
        $posts = factory(Post::class)->times(15)->create();

        $response = $this->get('/api/posts?limit=5');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($posts, 'posts', 1, 5)
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_limit_query_parameter_being_a_string()
    {
        /**
         * @var Collection $posts
         */
        $posts = factory(Post::class)->times(5)->create();

        $response = $this->get('/api/posts?limit=is+a+string');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_limit_query_parameter_being_a_negative_number()
    {
        /**
         * @var Collection $posts
         */
        $posts = factory(Post::class)->times(5)->create();

        $response = $this->get('/api/posts?limit=-1');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_limit_query_parameter_being_zero()
    {
        /**
         * @var Collection $posts
         */
        $posts = factory(Post::class)->times(5)->create();

        $response = $this->get('/api/posts?limit=0');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_with_limit_query_parameter_missing_value()
    {
        /**
         * @var Collection $posts
         */
        $posts = factory(Post::class)->times(5)->create();

        $response = $this->get('/api/posts?limit=');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }
}
