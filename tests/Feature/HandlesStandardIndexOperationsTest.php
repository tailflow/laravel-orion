<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Collection;
use Orion\Tests\Fixtures\App\Models\History;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;

class HandlesStandardIndexOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_list_of_resources()
    {
        /**
         * @var Collection $tags
         */
        $tags = factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags');

        $this->assertResourceListed($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $tags->map(function ($tag) {
                /**
                 * @var Tag $tag
                 */
                return $tag->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_paginated_list_of_resources()
    {
        /**
         * @var Collection $tags
         */
        $tags = factory(Tag::class)->times(45)->create();

        $response = $this->get('/api/tags?page=2');

        $this->assertResourceListed($response, 2, 16, 3, 15, 30, 45);
        $response->assertJson([
            'data' => $tags->forPage(2, 15)->values()->map(function ($tag) {
                /**
                 * @var Tag $tag
                 */
                return $tag->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_soft_delatable_resources_with_trashed()
    {
        /**
         * @var Collection $trashedTeams
         */
        $trashedTeams = factory(Team::class)->state('trashed')->times(5)->create();
        $teams = factory(Team::class)->times(5)->create();

        $response = $this->get('/api/teams?with_trashed=true');

        $this->assertResourceListed($response, 1, 1, 1, 15, 10, 10);
        $response->assertJson([
            'data' => $trashedTeams->merge($teams)->map(function ($team) {
                /**
                 * @var Team $team
                 */
                return $team->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_soft_delatable_resources_with_only_trashed()
    {
        /**
         * @var Collection $trashedTeams
         */
        $trashedTeams = factory(Team::class)->state('trashed')->times(5)->create();
        factory(Team::class)->times(5)->create();

        $response = $this->get('/api/teams?only_trashed=true');

        $this->assertResourceListed($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $trashedTeams->map(function ($team) {
                /**
                 * @var Team $team
                 */
                return $team->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function cannot_not_see_trashed_resources_if_query_parameters_are_missing()
    {
        /**
         * @var Collection $trashedTeams
         */
        $trashedTeams = factory(Team::class)->state('trashed')->times(5)->create();
        factory(Team::class)->times(5)->create();

        $response = $this->get('/api/teams');

        $this->assertResourceListed($response, 1, 1, 1, 15, 5, 5);
        $response->assertJsonMissing([
            'data' => $trashedTeams->map(function ($team) {
                /**
                 * @var Team $team
                 */
                return $team->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_if_disable_authorization_trait_is_applied()
    {
        /**
         * @var Collection $tags
         */
        factory(Tag::class)->times(5)->create();

        $response = $this->get('/api/tags');

        $this->assertResourceListed($response, 1, 1, 1, 15, 5, 5);
    }

    /** @test */
    public function cannot_get_a_list_of_resources_if_no_policy_is_defined_and_disable_authorizatin_trait_is_not_applied()
    {
        $supplier = factory(Supplier::class)->create();
        factory(History::class)->times(5)->create(['supplier_id' => $supplier]);

        $response = $this->get('/api/history', ['Accept' => 'application/json']);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function can_get_a_list_of_resources_when_allowed_by_a_policy()
    {
        $posts = factory(Post::class)->times(5)->create();

        $response = $this->actingAs(factory(User::class)->create(), 'api')->get('/api/posts');

        $this->assertResourceListed($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $posts->map(function ($post) {
                /**
                 * @var Post $post
                 */
                return $post->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_transformed_by_resource_resources()
    {
        $tagMeta = factory(TagMeta::class)->times(5)->create(['tag_id' => factory(Tag::class)->create()->id]);

        $response = $this->get('/api/tag_meta');

        $this->assertResourceListed($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $tagMeta->map(function ($tagMeta) {
                /**
                 * @var TagMeta $tagMeta
                 */
                return array_merge($tagMeta->toArray(), ['test-field-from-resource' => 'test-value']);
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_transformed_by_collection_resource_resources()
    {
        $suppliers = factory(Supplier::class)->times(5)->create();

        $response = $this->get('/api/suppliers');

        $this->assertResourceListed($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $suppliers->map(function ($supplier) {
                /**
                 * @var Supplier $supplier
                 */
                return $supplier->toArray();
            })->toArray(),
            'test-field-from-resource' => 'test-value'
        ]);
    }
}
