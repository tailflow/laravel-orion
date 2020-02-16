<?php

namespace Orion\Tests\Unit\Drivers\Standard;

use Illuminate\Routing\Route;
use Mockery;
use Orion\Drivers\Standard\ParamsValidator;
use Orion\Drivers\Standard\QueryBuilder;
use Orion\Drivers\Standard\RelationsResolver;
use Orion\Drivers\Standard\SearchBuilder;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Unit\Drivers\Standard\Stubs\ControllerStub;
use Orion\Tests\Unit\TestCase;

class QueryBuilderTest extends TestCase
{
    /** @test */
    public function building_query_for_index_endpoint()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/tags', [ControllerStub::class, 'index']);
        });

        $query = Tag::query();

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
            return new Route('GET', '/api/tags/1', [ControllerStub::class, 'show']);
        });

        $query = Tag::query();

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
            return new Route('GET', '/api/tags', [ControllerStub::class, 'index']);
        });
        $request->query->set('scopes', [
            ['name' => 'withPriority'],
            ['name' => 'whereNameAndPriority', 'parameters' => ['testTag', 1]]
        ]);

        $tagA = factory(Tag::class)->create(['name' => 'testTag', 'priority' => 1]);
        $tagB = factory(Tag::class)->create(['name' => 'testTag', 'priority' => null]);
        $tagC = factory(Tag::class)->create();

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator(['withPriority', 'whereNameAndPriority']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyScopesToQuery($query, $request);

        $tags = $query->get();

        $this->assertCount(1, $tags);
        $this->assertSame($tagA->id, $tags->first()->id);
    }

    /** @test */
    public function applying_root_level_fields_filters_with_singular_values()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'name', 'operator' => '=', 'value' => 'testTag'],
            ['type' => 'or', 'field' => 'priority', 'operator' => '=', 'value' => 5]
        ]);

        $tagA = factory(Tag::class)->create(['name' => 'testTag', 'priority' => 1]);
        $tagB = factory(Tag::class)->create(['name' => 'anotherTestTag', 'priority' => 5]);
        $tagC = factory(Tag::class)->create(['name' => 'customTag', 'priority' => 10]);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], ['name', 'priority']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyFiltersToQuery($query, $request);

        $tags = $query->get();

        $this->assertCount(2, $tags);
        $this->assertTrue($tags->contains('id', $tagA->id));
        $this->assertTrue($tags->contains('id', $tagB->id));
        $this->assertFalse($tags->contains('id', $tagC->id));
    }

    /** @test */
    public function applying_root_level_fields_filters_with_multiple_values()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'name', 'operator' => 'in', 'value' => ['testTagA', 'testTagB']],
            ['type' => 'or', 'field' => 'priority', 'operator' => 'in', 'value' => [5,10]]
        ]);

        $tagA = factory(Tag::class)->create(['name' => 'testTagA', 'priority' => 1]);
        $tagB = factory(Tag::class)->create(['name' => 'anotherTestTag', 'priority' => 5]);
        $tagC = factory(Tag::class)->create(['name' => 'customTag', 'priority' => 15]);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], ['name', 'priority']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyFiltersToQuery($query, $request);

        $tags = $query->get();

        $this->assertCount(2, $tags);
        $this->assertTrue($tags->contains('id', $tagA->id));
        $this->assertTrue($tags->contains('id', $tagB->id));
        $this->assertFalse($tags->contains('id', $tagC->id));
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
}
