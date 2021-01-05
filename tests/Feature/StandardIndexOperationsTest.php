<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Fixtures\App\Http\Resources\SampleCollectionResource;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class StandardIndexOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_resources_without_authorization(): void
    {
        factory(Post::class)->times(5)->create();

        Gate::policy(Post::class, RedPolicy::class);

        $response = $this->get('/api/posts');

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function getting_a_list_of_resources_when_authorized(): void
    {
        $user = factory(User::class)->create();
        $posts = factory(Post::class)->times(5)->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_resources_without_pagination(): void
    {
        $user = factory(User::class)->create();
        $posts = factory(Post::class)->times(20)->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        app()->bind('orion.paginationEnabled', function() {
           return false;
        });

        $response = $this->get('/api/posts');

        $this->assertResourcesListed(
            $response,
            $posts
        );
    }

    /** @test */
    public function getting_a_paginated_list_of_resources(): void
    {
        $posts = factory(Post::class)->times(45)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts?page=2');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts', 2)
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_resources_when_with_trashed_query_parameter_is_present(): void
    {
        $trashedPosts = factory(Post::class)->state('trashed')->times(5)->create();
        $posts = factory(Post::class)->times(5)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts?with_trashed=true');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($trashedPosts->merge($posts), 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_resources_when_only_trashed_query_parameter_is_present(): void
    {
        $trashedPosts = factory(Post::class)->state('trashed')->times(5)->create();
        factory(Post::class)->times(5)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts?only_trashed=true');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($trashedPosts, 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_resources_with_trashed_resources_filtered_out(): void
    {
        $trashedPosts = factory(Post::class)->state('trashed')->times(5)->create();
        $posts = factory(Post::class)->times(5)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts')
        );
        $response->assertJsonMissing([
            'data' => $trashedPosts->map(function (Post $post) {
                return $post->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function transforming_a_list_of_resources(): void
    {
        $posts = factory(Post::class)->times(5)->create();

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts'),
            ['test-field-from-resource' => 'test-value']
        );
    }

    /** @test */
    public function transforming_a_list_of_resources_using_collection_resource(): void
    {
        $posts = factory(Post::class)->times(5)->create();

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveCollectionResourceClass')->once()->andReturn(SampleCollectionResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts'),
            [],
            false
        );
        $response->assertJson([
            'test-field-from-resource' => 'test-value'
        ]);
    }

    /** @test */
    public function getting_a_list_of_resources_with_included_relation(): void
    {
        $posts = factory(Post::class)->times(5)->create()->map(function (Post $post) {
            $post->user()->associate(factory(User::class)->create());
            $post->save();
            $post->refresh();

            return $post->toArray();
        });

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get('/api/posts?include=user');

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }
}
