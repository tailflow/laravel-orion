<?php

namespace Orion\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\Company;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StandardIndexFilteringOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_equal_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        factory(Post::class)->create(['title' => 'not match'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_equal_operator_and_or_type(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        $anotherMatchingPost = factory(Post::class)->create(['position' => 3])->fresh();
        factory(Post::class)->create(['title' => 'not match'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => '=', 'value' => 'match'],
                ['type' => 'or', 'field' => 'position', 'operator' => '=', 'value' => 3]
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost, $anotherMatchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_not_equal_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->fresh();
        factory(Post::class)->create(['position' => 5])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'position', 'operator' => '!=', 'value' => 5]
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_less_than_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->fresh();
        factory(Post::class)->create(['position' => 5])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'position', 'operator' => '<', 'value' => 5]
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_less_than_or_equal_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->fresh();
        $anotherMatchingPost = factory(Post::class)->create(['position' => 5])->fresh();
        factory(Post::class)->create(['position' => 6])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'position', 'operator' => '<=', 'value' => 5],
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost, $anotherMatchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_more_than_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->fresh();
        factory(Post::class)->create(['position' => 2])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'position', 'operator' => '>', 'value' => 3]
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_more_than_or_equal_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->fresh();
        $anotherMatchingPost = factory(Post::class)->create(['position' => 5])->fresh();
        factory(Post::class)->create(['position' => 3])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'position', 'operator' => '>=', 'value' => 4],
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost, $anotherMatchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_like_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        $anotherMatchingPost = factory(Post::class)->create(['title' => 'another match'])->fresh();
        factory(Post::class)->create(['title' => 'different'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => 'like', 'value' => '%match%'],
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost, $anotherMatchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_not_like_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'another match'])->fresh();
        factory(Post::class)->create(['title' => 'match'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => 'not like', 'value' => 'match%'],
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_in_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        $anotherMatchingPost = factory(Post::class)->create(['title' => 'another match'])->fresh();
        factory(Post::class)->create(['title' => 'different'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => 'in', 'value' => ['match', 'another match']],
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost, $anotherMatchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_not_in_operator(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        factory(Post::class)->create(['title' => 'different'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => 'not in', 'value' => ['different']],
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_relation_field_resources(): void
    {
        $matchingPostUser = factory(User::class)->create(['name' => 'match']);
        $matchingPost = factory(Post::class)->create(['user_id' => $matchingPostUser->id])->fresh();

        $nonMatchingPostUser = factory(User::class)->make(['name' => 'not match']);
        factory(Post::class)->create(['user_id' => $nonMatchingPostUser->id])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'user.name', 'operator' => '=', 'value' => 'match'],
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_not_whitelisted_field(): void
    {
        factory(Post::class)->create(['body' => 'match'])->fresh();
        factory(Post::class)->create(['body' => 'not match'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'body', 'operator' => '=', 'value' => 'match']
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['filters.0.field']]);
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_with_wildcard_whitelisting(): void
    {
        $matchingTeam = factory(Team::class)->create(['name' => 'match'])->fresh();
        factory(Team::class)->create(['name' => 'not match'])->fresh();

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post('/api/teams/search', [
            'filters' => [
                ['field' => 'name', 'operator' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingTeam], 'teams/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_relation_field_with_wildcard_whitelisting(): void
    {
        $matchingTeamCompany = factory(Company::class)->create(['name' => 'match'])->fresh();
        $matchingTeam = factory(Team::class)->create(['company_id' => $matchingTeamCompany->id])->fresh();
        $nonMatchingTeamCompany = factory(Company::class)->create(['name' => 'not match'])->fresh();
        factory(Team::class)->create(['company_id' => $nonMatchingTeamCompany->id])->fresh();

        Gate::policy(Team::class, GreenPolicy::class);

        $response = $this->post('/api/teams/search', [
            'filters' => [
                ['field' => 'company.name', 'operator' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingTeam], 'teams/search')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_nullable_value(): void
    {
        $matchingPost = factory(Post::class)->create(['publish_at' => null])->fresh();
        factory(Post::class)->create(['publish_at' =>  Carbon::now()])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'publish_at', 'operator' => '=', 'value' => null]
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }
}
