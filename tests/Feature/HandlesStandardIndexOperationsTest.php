<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Collection;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\Team;

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

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
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

        $this->assertSuccessfulIndexResponse($response, 2, 16, 3, 15, 30, 45);
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

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 10, 10);
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

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
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

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
        $response->assertJsonMissing([
            'data' => $trashedTeams->map(function ($team) {
                /**
                 * @var Team $team
                 */
                return $team->toArray();
            })->toArray()
        ]);

    }
}
