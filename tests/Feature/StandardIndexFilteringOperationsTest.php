<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Company;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;

class StandardIndexFilteringOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_equal_operator()
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->refresh();
        factory(Post::class)->create(['title' => 'not match'])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_equal_operator_and_or_type()
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->refresh();
        $anotherMatchingPost = factory(Post::class)->create(['position' => 3])->refresh();
        factory(Post::class)->create(['title' => 'not match'])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => '=', 'value' => 'match'],
                ['type' => 'or', 'field' => 'position', 'operator' => '=', 'value' => 3]
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost, $anotherMatchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_not_equal_operator()
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->refresh();
        factory(Post::class)->create(['position' => 5])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'position', 'operator' => '!=', 'value' => 5]
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_less_than_operator()
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->refresh();
        factory(Post::class)->create(['position' => 5])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'position', 'operator' => '<', 'value' => 5]
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_less_than_or_equal_operator()
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->refresh();
        $anotherMatchingPost = factory(Post::class)->create(['position' => 5])->refresh();
        factory(Post::class)->create(['position' => 6])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'position', 'operator' => '<=', 'value' => 5],
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost, $anotherMatchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_more_than_operator()
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->refresh();
        factory(Post::class)->create(['position' => 2])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'position', 'operator' => '>', 'value' => 3]
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_more_than_or_equal_operator()
    {
        $matchingPost = factory(Post::class)->create(['position' => 4])->refresh();
        $anotherMatchingPost = factory(Post::class)->create(['position' => 5])->refresh();
        factory(Post::class)->create(['position' => 3])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'position', 'operator' => '>=', 'value' => 4],
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost, $anotherMatchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_like_operator()
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->refresh();
        $anotherMatchingPost = factory(Post::class)->create(['title' => 'another match'])->refresh();
        factory(Post::class)->create(['title' => 'different'])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => 'like', 'value' => '%match%'],
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost, $anotherMatchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_not_like_operator()
    {
        $matchingPost = factory(Post::class)->create(['title' => 'another match'])->refresh();
        factory(Post::class)->create(['title' => 'match'])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => 'not like', 'value' => 'match%'],
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_in_operator()
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->refresh();
        $anotherMatchingPost = factory(Post::class)->create(['title' => 'another match'])->refresh();
        factory(Post::class)->create(['title' => 'different'])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => 'in', 'value' => ['match', 'another match']],
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost, $anotherMatchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_using_not_in_operator()
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->refresh();
        factory(Post::class)->create(['title' => 'different'])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'title', 'operator' => 'not in', 'value' => ['different']],
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_relation_field_resources()
    {
        $matchingPostUser = factory(User::class)->create(['name' => 'match']);
        $matchingPost = factory(Post::class)->create(['user_id' => $matchingPostUser->id])->refresh();

        $nonMatchingPostUser = factory(User::class)->make(['name' => 'not match']);
        factory(Post::class)->create(['user_id' => $nonMatchingPostUser->id])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'user.name', 'operator' => '=', 'value' => 'match'],
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingPost]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_not_whitelisted_field()
    {
        factory(Post::class)->create(['body' => 'match'])->refresh();
        factory(Post::class)->create(['body' => 'not match'])->refresh();

        $response = $this->post('/api/posts/search', [
            'filters' => [
                ['field' => 'body', 'operator' => '=', 'value' => 'match']
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['filters.0.field']]);
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_model_field_with_wildcard_whitelisting()
    {
        $matchingTeam = factory(Team::class)->create(['name' => 'match'])->refresh();
        factory(Team::class)->create(['name' => 'not match'])->refresh();

        $response = $this->bypassAuthorization()->post('/api/teams/search', [
            'filters' => [
                ['field' => 'name', 'operator' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingTeam]));
    }

    /** @test */
    public function getting_a_list_of_resources_filtered_by_relation_field_with_wildcard_whitelisting()
    {
        $matchingTeamCompany = factory(Company::class)->create(['name' => 'match'])->refresh();
        $matchingTeam = factory(Team::class)->create(['company_id' => $matchingTeamCompany->id])->refresh();
        $nonMatchingTeamCompany = factory(Company::class)->create(['name' => 'not match'])->refresh();
        factory(Team::class)->create(['company_id' => $nonMatchingTeamCompany->id])->refresh();

        $response = $this->bypassAuthorization()->post('/api/teams/search', [
            'filters' => [
                ['field' => 'company.name', 'operator' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourceListed($response, collect([$matchingTeam]));
    }
}
