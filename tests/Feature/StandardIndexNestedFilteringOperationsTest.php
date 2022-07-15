<?php

namespace Orion\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\Company;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StandardIndexNestedFilteringOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_resources_nested_filtered_by_model_field_using_default_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        factory(Post::class)->create(['title' => 'not match'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'filters' => [
                    ['field' => 'title', 'operator' => 'in' ,'value' => ['match', 'not_match']],
                    ['nested' => [
                        ['field' => 'title', 'value' => 'match'],
                        ['field' => 'title', 'operator' => '!=', 'value' => 'not match']
                    ]],
                ],
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_nested_filtered_by_model_field_using_equal_operator_and_or_type(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        $anotherMatchingPost = factory(Post::class)->create(['position' => 3])->fresh();
        factory(Post::class)->create(['title' => 'not match'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'filters' => [
                    ['field' => 'title', 'operator' => '=', 'value' => 'match'],
                    ['type' => 'or', 'nested' => [
                        ['field' => 'position', 'operator' => '=', 'value' => 3],
                    ]],
                ],
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost, $anotherMatchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_nested_filtered_by_model_field_using_not_equal_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->fresh();
        factory(Post::class)->create(['position' => 5])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'filters' => [
                    ['nested' => [
                        ['field' => 'position', 'operator' => '!=', 'value' => 5]
                    ]],
                ],
            ]
        );

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_nested_filtered_by_not_whitelisted_field(): void
    {
        factory(Post::class)->create(['body' => 'match'])->fresh();
        factory(Post::class)->create(['body' => 'not match'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts/search',
            [
                'filters' => [
                    ['nested' => [
                        ['field' => 'body', 'operator' => '=', 'value' => 'match']
                    ]],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['filters.0.nested.0.field']]);
    }
}
