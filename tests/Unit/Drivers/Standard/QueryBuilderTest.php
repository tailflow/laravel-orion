<?php

namespace Orion\Tests\Unit\Drivers\Standard;

use Illuminate\Routing\Route;
use Mockery;
use Orion\Drivers\Standard\ParamsValidator;
use Orion\Drivers\Standard\QueryBuilder;
use Orion\Drivers\Standard\RelationsResolver;
use Orion\Drivers\Standard\SearchBuilder;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Unit\Drivers\Standard\Stubs\ControllerStub;
use Orion\Tests\Unit\TestCase;

class QueryBuilderTest extends TestCase
{
    /** @test */
    public function building_query_for_index_endpoint()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/posts', [ControllerStub::class, 'index']);
        });

        $query = Post::query();

        $queryBuilderMock = Mockery::mock(QueryBuilder::class)->makePartial();
        $queryBuilderMock->shouldReceive('applyScopesToQuery')->with($query, $request)->once();
        $queryBuilderMock->shouldReceive('applyFiltersToQuery')->with($query, $request)->once();
        $queryBuilderMock->shouldReceive('applySearchingToQuery')->with($query, $request)->once();
        $queryBuilderMock->shouldReceive('applySortingToQuery')->with($query, $request)->once();
        $queryBuilderMock->shouldReceive('applySoftDeletesToQuery')->with($query, $request)->once();

        $this->assertSame($query, $queryBuilderMock->buildQuery($query, $request));
    }

    /** @test */
    public function building_query_for_show_endpoint()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/posts/1', [ControllerStub::class, 'show']);
        });

        $query = Post::query();

        $queryBuilderMock = Mockery::mock(QueryBuilder::class)->makePartial();
        $queryBuilderMock->shouldReceive('applyScopesToQuery')->with($query, $request)->never();
        $queryBuilderMock->shouldReceive('applyFiltersToQuery')->with($query, $request)->never();
        $queryBuilderMock->shouldReceive('applySearchingToQuery')->with($query, $request)->never();
        $queryBuilderMock->shouldReceive('applySortingToQuery')->with($query, $request)->never();
        $queryBuilderMock->shouldReceive('applySoftDeletesToQuery')->with($query, $request)->once();

        $this->assertSame($query, $queryBuilderMock->buildQuery($query, $request));
    }

    /** @test */
    public function applying_scopes_to_query()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/posts', [ControllerStub::class, 'index']);
        });
        $request->query->set('scopes', [
            ['name' => 'published'],
            ['name' => 'publishedAt', 'parameters' => ['2019-01-01 09:35:14']]
        ]);

        $postA = factory(Post::class)->create(['publish_at' => '2019-01-01 09:35:14']);
        $postB = factory(Post::class)->create(['publish_at' => '2020-02-01 09:35:14']);
        $postC = factory(Post::class)->create();

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator(['published', 'publishedAt']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyScopesToQuery($query, $request);

        $posts = $query->get();

        $this->assertCount(1, $posts);
        $this->assertSame($postA->id, $posts->first()->id);
    }

    /** @test */
    public function applying_model_level_fields_filters_with_singular_values()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'title', 'operator' => '=', 'value' => 'test post'],
            ['type' => 'or', 'field' => 'tracking_id', 'operator' => '=', 'value' => 5]
        ]);

        $postA = factory(Post::class)->create(['title' => 'test post', 'tracking_id' => 1]);
        $postB = factory(Post::class)->create(['title' => 'another test post', 'tracking_id' => 5]);
        $postC = factory(Post::class)->create(['title' => 'different post', 'tracking_id' => 10]);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], ['title', 'tracking_id']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyFiltersToQuery($query, $request);

        $posts = $query->get();

        $this->assertCount(2, $posts);
        $this->assertTrue($posts->contains('id', $postA->id));
        $this->assertTrue($posts->contains('id', $postB->id));
        $this->assertFalse($posts->contains('id', $postC->id));
    }

    /** @test */
    public function applying_model_level_fields_filters_with_multiple_values()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'title', 'operator' => 'in', 'value' => ['test post', 'something else']],
            ['type' => 'or', 'field' => 'tracking_id', 'operator' => 'in', 'value' => [5, 10]]
        ]);

        $postA = factory(Post::class)->create(['title' => 'test post', 'tracking_id' => 1]);
        $postB = factory(Post::class)->create(['title' => 'another test post', 'tracking_id' => 10]);
        $postC = factory(Post::class)->create(['title' => 'different post', 'tracking_id' => 15]);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], ['title', 'tracking_id']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyFiltersToQuery($query, $request);

        $tags = $query->get();

        $this->assertCount(2, $tags);
        $this->assertTrue($tags->contains('id', $postA->id));
        $this->assertTrue($tags->contains('id', $postB->id));
        $this->assertFalse($tags->contains('id', $postC->id));
    }

    /** @test */
    public function applying_relation_level_fields_filters_with_singular_values()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'user.name', 'operator' => '=', 'value' => 'test user A'],
            ['type' => 'or', 'field' => 'user.name', 'operator' => '=', 'value' => 'test user B']
        ]);

        $postAUser = factory(User::class)->create(['name' => 'test user A']);
        $postA = factory(Post::class)->create(['user_id' => $postAUser->id]);

        $postBUser = factory(User::class)->create(['name' => 'test user B']);
        $postB = factory(Post::class)->create(['user_id' => $postBUser->id]);

        $postCUser = factory(User::class)->create(['name' => 'test user C']);
        $postC = factory(Post::class)->create(['user_id' => $postCUser->id]);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], ['user.name']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyFiltersToQuery($query, $request);

        $posts = $query->get();

        $this->assertCount(2, $posts);
        $this->assertTrue($posts->contains('id', $postA->id));
        $this->assertTrue($posts->contains('id', $postB->id));
        $this->assertFalse($posts->contains('id', $postC->id));
    }

    /** @test */
    public function applying_relation_level_fields_filters_with_multiple_values()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'user.name', 'operator' => 'in', 'value' => ['test user A', 'test user B']],
            ['type' => 'or', 'field' => 'user.name', 'operator' => 'in', 'value' => ['test user C']]
        ]);

        $postAUser = factory(User::class)->create(['name' => 'test user A']);
        $postA = factory(Post::class)->create(['user_id' => $postAUser->id]);

        $postBUser = factory(User::class)->create(['name' => 'test user B']);
        $postB = factory(Post::class)->create(['user_id' => $postBUser->id]);

        $postCUser = factory(User::class)->create(['name' => 'test user C']);
        $postC = factory(Post::class)->create(['user_id' => $postCUser->id]);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], ['user.name']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyFiltersToQuery($query, $request);

        $posts = $query->get();

        $this->assertCount(3, $posts);
        $this->assertTrue($posts->contains('id', $postA->id));
        $this->assertTrue($posts->contains('id', $postB->id));
        $this->assertTrue($posts->contains('id', $postC->id));
    }

    /** @test */
    public function applying_filters_with_not_in_operator()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'title', 'operator' => 'not in', 'value' => ['test post A', 'test post B']]
        ]);

        $postA = factory(Post::class)->create(['title' => 'test post A']);
        $postB = factory(Post::class)->create(['title' => 'test post B']);
        $postC = factory(Post::class)->create(['title' => 'test post C']);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], ['title']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyFiltersToQuery($query, $request);

        $posts = $query->get();

        $this->assertCount(1, $posts);
        $this->assertFalse($posts->contains('id', $postA->id));
        $this->assertFalse($posts->contains('id', $postB->id));
        $this->assertTrue($posts->contains('id', $postC->id));
    }

    /** @test */
    public function searching_on_model_fields()
    {
        $request = $this->makeRequestWithSearch([
            'value' => 'example'
        ]);

        $postA = factory(Post::class)->create(['title' => 'title example']);
        $postB = factory(Post::class)->create(['title' => 'example title']);
        $postC = factory(Post::class)->create(['title' => 'title with example in the middle']);
        $postD = factory(Post::class)->create(['title' => 'not matching title', 'body' => 'but matching example body']);
        $postE = factory(Post::class)->create(['title' => 'not matching title']);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder(['title', 'body'])
        );
        $queryBuilder->applySearchingToQuery($query, $request);

        $posts = $query->get();

        $this->assertCount(4, $posts);
        $this->assertTrue($posts->contains('id', $postA->id));
        $this->assertTrue($posts->contains('id', $postB->id));
        $this->assertTrue($posts->contains('id', $postC->id));
        $this->assertTrue($posts->contains('id', $postD->id));
        $this->assertFalse($posts->contains('id', $postE->id));
    }

    /** @test */
    public function searching_on_relation_fields()
    {
        $request = $this->makeRequestWithSearch([
            'value' => 'example'
        ]);

        $postAUser = factory(User::class)->create(['name' => 'name example']);
        $postA = factory(Post::class)->create(['user_id' => $postAUser->id]);

        $postBUser = factory(User::class)->create(['name' => 'example name']);
        $postB = factory(Post::class)->create(['user_id' => $postBUser->id]);

        $postCUser = factory(User::class)->create(['name' => 'name with example in the middle']);
        $postC = factory(Post::class)->create(['user_id' => $postCUser->id]);

        $postDUser = factory(User::class)->create(['name' => 'not matching name', 'email' => 'but-matching-email@example.com']);
        $postD = factory(Post::class)->create(['user_id' => $postDUser->id]);

        $postEUser = factory(User::class)->create(['name' => 'not matching name', 'email' => 'test@domain.com']);
        $postE = factory(Post::class)->create(['user_id' => $postEUser->id]);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder(['user.name', 'user.email'])
        );
        $queryBuilder->applySearchingToQuery($query, $request);

        $posts = $query->get();

        $this->assertCount(4, $posts);
        $this->assertTrue($posts->contains('id', $postA->id));
        $this->assertTrue($posts->contains('id', $postB->id));
        $this->assertTrue($posts->contains('id', $postC->id));
        $this->assertTrue($posts->contains('id', $postD->id));
        $this->assertFalse($posts->contains('id', $postE->id));
    }

    /** @test */
    public function search_query_constraints_are_not_applied_if_descriptor_is_missing_in_request()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/posts', [ControllerStub::class, 'index']);
        });

        factory(Post::class)->times(3)->create(['title' => 'not matching']);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder(['title', 'body'])
        );
        $queryBuilder->applySearchingToQuery($query, $request);

        $posts = $query->get();

        $this->assertCount(3, $posts);
    }

    /** @test */
    public function default_sorting_based_on_model_fields()
    {
        $request = $this->makeRequestWithSort([
            ['field' => 'title']
        ]);

        $postA = factory(Post::class)->create(['title' => 'post A']);
        $postB = factory(Post::class)->create(['title' => 'post B']);
        $postC = factory(Post::class)->create(['title' => 'post C']);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], [], ['title']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applySortingToQuery($query, $request);

        $posts = $query->get();

        $this->assertEquals($postA->id, $posts[0]->id);
        $this->assertEquals($postB->id, $posts[1]->id);
        $this->assertEquals($postC->id, $posts[2]->id);
    }

    /** @test */
    public function desc_sorting_based_on_model_fields()
    {
        $request = $this->makeRequestWithSort([
            ['field' => 'title', 'direction' => 'desc']
        ]);

        $postA = factory(Post::class)->create(['title' => 'post A']);
        $postB = factory(Post::class)->create(['title' => 'post B']);
        $postC = factory(Post::class)->create(['title' => 'post C']);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], [], ['title']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applySortingToQuery($query, $request);

        $posts = $query->get();

        $this->assertEquals($postC->id, $posts[0]->id);
        $this->assertEquals($postB->id, $posts[1]->id);
        $this->assertEquals($postA->id, $posts[2]->id);
    }

    //TODO: test sorting on different relation types

    /** @test */
    public function default_sorting_based_on_relation_fields()
    {
        $request = $this->makeRequestWithSort([
            ['field' => 'user.name']
        ]);

        $postAUser = factory(User::class)->create(['name' => 'user A']);
        $postA = factory(Post::class)->create(['user_id' => $postAUser->id]);

        $postBUser = factory(User::class)->create(['name' => 'user B']);
        $postB = factory(Post::class)->create(['user_id' => $postBUser->id]);

        $postCUser = factory(User::class)->create(['name' => 'user C']);
        $postC = factory(Post::class)->create(['user_id' => $postCUser->id]);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], [], ['user.name']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applySortingToQuery($query, $request);

        $posts = $query->get();

        $this->assertEquals($postA->id, $posts[0]->id);
        $this->assertEquals($postB->id, $posts[1]->id);
        $this->assertEquals($postC->id, $posts[2]->id);
    }

    /** @test */
    public function desc_sorting_based_on_relation_fields()
    {
        $request = $this->makeRequestWithSort([
            ['field' => 'user.name', 'direction' => 'desc']
        ]);

        $postAUser = factory(User::class)->create(['name' => 'user A']);
        $postA = factory(Post::class)->create(['user_id' => $postAUser->id]);

        $postBUser = factory(User::class)->create(['name' => 'user B']);
        $postB = factory(Post::class)->create(['user_id' => $postBUser->id]);

        $postCUser = factory(User::class)->create(['name' => 'user C']);
        $postC = factory(Post::class)->create(['user_id' => $postCUser->id]);

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], [], ['user.name']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applySortingToQuery($query, $request);

        $posts = $query->get();

        $this->assertEquals($postC->id, $posts[0]->id);
        $this->assertEquals($postB->id, $posts[1]->id);
        $this->assertEquals($postA->id, $posts[2]->id);
    }

    /** @test */
    public function soft_deletes_query_constraints_are_not_applied_if_model_is_not_soft_deletable()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/teams', [ControllerStub::class, 'index']);
        });

        $queryMock = Mockery::mock(Team::query())->makePartial();
        $queryMock->shouldNotReceive('withTrashed');
        $queryMock->shouldNotReceive('onlyTrashed');

        $queryBuilder = new QueryBuilder(
            Team::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );

        $this->assertFalse($queryBuilder->applySoftDeletesToQuery($queryMock, $request));
    }

    /** @test */
    public function trashed_models_are_returned_when_requested()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/posts', [ControllerStub::class, 'index']);
        });
        $request->query->set('with_trashed', true);

        $post = factory(Post::class)->create();
        $softDeletedPost = factory(Post::class)->state('trashed')->create();

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $this->assertTrue($queryBuilder->applySoftDeletesToQuery($query, $request));

        $posts = $query->get();

        $this->assertCount(2, $posts);
        $this->assertTrue($posts->contains('id', $post->id));
        $this->assertTrue($posts->contains('id', $softDeletedPost->id));
    }

    /** @test */
    public function only_trashed_models_are_returned_when_requested()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/posts', [ControllerStub::class, 'index']);
        });
        $request->query->set('only_trashed', true);

        $post = factory(Post::class)->create();
        $softDeletedPost = factory(Post::class)->state('trashed')->create();

        $query = Post::query();

        $queryBuilder = new QueryBuilder(
            Post::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $this->assertTrue($queryBuilder->applySoftDeletesToQuery($query, $request));

        $posts = $query->get();

        $this->assertCount(1, $posts);
        $this->assertFalse($posts->contains('id', $post->id));
        $this->assertTrue($posts->contains('id', $softDeletedPost->id));
    }

    protected function makeRequestWithFilters(array $filters)
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/tags', [ControllerStub::class, 'index']);
        });
        $request->query->set('filters', $filters);

        return $request;
    }

    protected function makeRequestWithSearch(array $search)
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/tags', [ControllerStub::class, 'index']);
        });
        $request->query->set('search', $search);

        return $request;
    }

    protected function makeRequestWithSort(array $sort)
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/tags', [ControllerStub::class, 'index']);
        });
        $request->query->set('sort', $sort);

        return $request;
    }
}
