<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Fixtures\App\Http\Resources\SampleCollectionResource;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\PostPolicy;

class StandardIndexOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_resources_without_authorization()
    {
        /**
         * @var Collection $posts
         */
        factory(Post::class)->times(5)->create();

        $response = $this->requireAuthorization()->get('/api/posts');

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function getting_a_list_of_resources_when_authorized()
    {
        $user = factory(User::class)->create();
        /**
         * @var Collection $posts
         */
        $posts = factory(Post::class)->times(5)->create(['user_id' => $user->id]);

        Gate::policy(Post::class, PostPolicy::class);

        $response = $this->requireAuthorization()->withAuth($user)->get('/api/posts');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }

    /** @test */
    public function getting_a_paginated_list_of_resources()
    {
        /**
         * @var Collection $posts
         */
        $posts = factory(Post::class)->times(45)->create();

        $response = $this->get('/api/posts?page=2');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($posts, 'posts', 2)
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_resources_when_with_trashed_query_parameter_is_present()
    {
        /**
         * @var Collection $trashedPosts
         */
        $trashedPosts = factory(Post::class)->state('trashed')->times(5)->create();
        $posts = factory(Post::class)->times(5)->create();

        $response = $this->get('/api/posts?with_trashed=true');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($trashedPosts->merge($posts), 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_resources_when_only_trashed_query_parameter_is_present()
    {
        /**
         * @var Collection $trashedPosts
         */
        $trashedPosts = factory(Post::class)->state('trashed')->times(5)->create();
        $posts = factory(Post::class)->times(5)->create();

        $response = $this->get('/api/posts?only_trashed=true');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($trashedPosts, 'posts')
        );
    }

    /** @test */
    public function getting_a_list_of_soft_deletable_resources_with_trashed_resources_filtered_out()
    {
        /**
         * @var Collection $trashedPosts
         */
        $trashedPosts = factory(Post::class)->state('trashed')->times(5)->create();
        $posts = factory(Post::class)->times(5)->create();

        $response = $this->get('/api/posts');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($posts, 'posts')
        );
        $response->assertJsonMissing([
            'data' => $trashedPosts->map(function ($post) {
                /**
                 * @var Post $post
                 */
                return $post->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function transforming_a_list_of_resources()
    {
        /**
         * @var Collection $posts
         */
        $posts = factory(Post::class)->times(5)->create();

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->get('/api/posts');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($posts, 'posts'),
            ['test-field-from-resource' => 'test-value']
        );
    }

    /** @test */
    public function transforming_a_list_of_resources_using_collection_resource()
    {
        /**
         * @var Collection $posts
         */
        $posts = factory(Post::class)->times(5)->create();

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveCollectionResourceClass')->once()->andReturn(SampleCollectionResource::class);

            return $componentsResolverMock;
        });

        $response = $this->get('/api/posts');

        $this->assertResourceListed(
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
    public function getting_a_list_of_resources_with_included_relation()
    {
        $posts = factory(Post::class)->times(5)->create()->map(function (Post $post) {
            $post->user()->associate(factory(User::class)->create());
            $post->save();
            $post->refresh();

            return $post->toArray();
        });

        $response = $this->get('/api/posts?include=user');

        $this->assertResourceListed(
            $response,
            $this->makePaginator($posts, 'posts')
        );
    }
}
