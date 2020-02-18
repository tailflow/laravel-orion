<?php

namespace Orion\Tests\Unit\Drivers\Standard;

use Illuminate\Routing\Route;
use Mockery;
use Orion\Drivers\Standard\ParamsValidator;
use Orion\Drivers\Standard\QueryBuilder;
use Orion\Drivers\Standard\RelationsResolver;
use Orion\Drivers\Standard\SearchBuilder;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\History;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
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
    public function applying_model_level_fields_filters_with_singular_values()
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
    public function applying_model_level_fields_filters_with_multiple_values()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'name', 'operator' => 'in', 'value' => ['testTagA', 'testTagB']],
            ['type' => 'or', 'field' => 'priority', 'operator' => 'in', 'value' => [5, 10]]
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

    /** @test */
    public function applying_relation_level_fields_filters_with_singular_values()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'meta.key', 'operator' => '=', 'value' => 'testKeyA'],
            ['type' => 'or', 'field' => 'meta.key', 'operator' => '=', 'value' => 'testKeyB']
        ]);

        $tagA = factory(Tag::class)->create(['name' => 'testTagA']);
        factory(TagMeta::class)->create(['tag_id' => $tagA->id, 'key' => 'testKeyA']);
        $tagB = factory(Tag::class)->create(['name' => 'testTagB']);
        factory(TagMeta::class)->create(['tag_id' => $tagB->id, 'key' => 'testKeyB']);
        $tagC = factory(Tag::class)->create(['name' => 'testTagC']);
        factory(TagMeta::class)->create(['tag_id' => $tagC->id, 'key' => 'testKeyC']);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], ['meta.key']),
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
    public function applying_relation_level_fields_filters_with_multiple_values()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'meta.key', 'operator' => 'in', 'value' => ['testKeyA', 'testKeyB']],
            ['type' => 'or', 'field' => 'meta.key', 'operator' => 'in', 'value' => ['testKeyC']]
        ]);

        $tagA = factory(Tag::class)->create(['name' => 'testTagA']);
        factory(TagMeta::class)->create(['tag_id' => $tagA->id, 'key' => 'testKeyA']);
        $tagB = factory(Tag::class)->create(['name' => 'testTagB']);
        factory(TagMeta::class)->create(['tag_id' => $tagB->id, 'key' => 'testKeyB']);
        $tagC = factory(Tag::class)->create(['name' => 'testTagC']);
        factory(TagMeta::class)->create(['tag_id' => $tagC->id, 'key' => 'testKeyC']);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], ['meta.key']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyFiltersToQuery($query, $request);

        $tags = $query->get();

        $this->assertCount(3, $tags);
        $this->assertTrue($tags->contains('id', $tagA->id));
        $this->assertTrue($tags->contains('id', $tagB->id));
        $this->assertTrue($tags->contains('id', $tagC->id));
    }

    /** @test */
    public function applying_filters_with_not_in_operator()
    {
        $request = $this->makeRequestWithFilters([
            ['field' => 'name', 'operator' => 'not in', 'value' => ['testTagA', 'testTagB']]
        ]);

        $tagA = factory(Tag::class)->create(['name' => 'testTagA']);
        $tagB = factory(Tag::class)->create(['name' => 'testTagB']);
        $tagC = factory(Tag::class)->create(['name' => 'testTagC']);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], ['name']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applyFiltersToQuery($query, $request);

        $tags = $query->get();

        $this->assertCount(1, $tags);
        $this->assertFalse($tags->contains('id', $tagA->id));
        $this->assertFalse($tags->contains('id', $tagB->id));
        $this->assertTrue($tags->contains('id', $tagC->id));
    }

    /** @test */
    public function searching_on_model_fields()
    {
        $request = $this->makeRequestWithSearch([
            'value' => 'example'
        ]);

        $tagA = factory(Tag::class)->create(['name' => 'name example']);
        $tagB = factory(Tag::class)->create(['name' => 'example name']);
        $tagC = factory(Tag::class)->create(['name' => 'name with example in the middle']);
        $tagD = factory(Tag::class)->create(['name' => 'not matching name', 'description' => 'but matching example description']);
        $tagE = factory(Tag::class)->create(['name' => 'not matching name']);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder(['name', 'description'])
        );
        $queryBuilder->applySearchingToQuery($query, $request);

        $tags = $query->get();

        $this->assertCount(4, $tags);
        $this->assertTrue($tags->contains('id', $tagA->id));
        $this->assertTrue($tags->contains('id', $tagB->id));
        $this->assertTrue($tags->contains('id', $tagC->id));
        $this->assertTrue($tags->contains('id', $tagD->id));
        $this->assertFalse($tags->contains('id', $tagE->id));
    }

    /** @test */
    public function searching_on_relation_fields()
    {
        $request = $this->makeRequestWithSearch([
            'value' => 'example'
        ]);

        $tagA = factory(Tag::class)->create(['name' => 'testTagA']);
        factory(TagMeta::class)->create(['tag_id' => $tagA->id, 'key' => 'key example']);
        $tagB = factory(Tag::class)->create(['name' => 'testTagB']);
        factory(TagMeta::class)->create(['tag_id' => $tagB->id, 'key' => 'example key']);
        $tagC = factory(Tag::class)->create(['name' => 'testTagC']);
        factory(TagMeta::class)->create(['tag_id' => $tagC->id, 'key' => 'key with example in the middle']);
        $tagD = factory(Tag::class)->create(['name' => 'testTagD']);
        factory(TagMeta::class)->create(['tag_id' => $tagD->id, 'key' => 'not matching key', 'value' => 'but matching example value']);
        $tagE = factory(Tag::class)->create(['name' => 'testTagE']);
        factory(TagMeta::class)->create(['tag_id' => $tagE->id, 'key' => 'not matching key']);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder(['meta.key', 'meta.value'])
        );
        $queryBuilder->applySearchingToQuery($query, $request);

        $tags = $query->get();

        $this->assertCount(4, $tags);
        $this->assertTrue($tags->contains('id', $tagA->id));
        $this->assertTrue($tags->contains('id', $tagB->id));
        $this->assertTrue($tags->contains('id', $tagC->id));
        $this->assertTrue($tags->contains('id', $tagD->id));
        $this->assertFalse($tags->contains('id', $tagE->id));
    }

    /** @test */
    public function search_query_constraints_are_not_applied_if_descriptor_is_missing_in_request()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/tags', [ControllerStub::class, 'index']);
        });

        factory(Tag::class)->times(3)->create(['name' => 'not matching']);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder(['name', 'description'])
        );
        $queryBuilder->applySearchingToQuery($query, $request);

        $tags = $query->get();

        $this->assertCount(3, $tags);
    }

    /** @test */
    public function default_sorting_based_on_model_fields()
    {
        $request = $this->makeRequestWithSort([
            ['field' => 'name']
        ]);

        $tagA = factory(Tag::class)->create(['name' => 'tagA']);
        $tagB = factory(Tag::class)->create(['name' => 'tagB']);
        $tagC = factory(Tag::class)->create(['name' => 'tagC']);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], [], ['name']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applySortingToQuery($query, $request);

        $tags = $query->get();

        $this->assertEquals($tagA->id, $tags[0]->id);
        $this->assertEquals($tagB->id, $tags[1]->id);
        $this->assertEquals($tagC->id, $tags[2]->id);
    }

    /** @test */
    public function desc_sorting_based_on_model_fields()
    {
        $request = $this->makeRequestWithSort([
            ['field' => 'name', 'direction' => 'desc']
        ]);

        $tagA = factory(Tag::class)->create(['name' => 'tagA']);
        $tagB = factory(Tag::class)->create(['name' => 'tagB']);
        $tagC = factory(Tag::class)->create(['name' => 'tagC']);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], [], ['name']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applySortingToQuery($query, $request);

        $tags = $query->get();

        $this->assertEquals($tagC->id, $tags[0]->id);
        $this->assertEquals($tagB->id, $tags[1]->id);
        $this->assertEquals($tagA->id, $tags[2]->id);
    }

    //TODO: test sorting on different relation types

    /** @test */
    public function default_sorting_based_on_relation_fields()
    {
        $request = $this->makeRequestWithSort([
            ['field' => 'meta.key']
        ]);

        $tagA = factory(Tag::class)->create();
        factory(TagMeta::class)->create(['tag_id' => $tagA->id, 'key' => 'metaA']);
        $tagB = factory(Tag::class)->create();
        factory(TagMeta::class)->create(['tag_id' => $tagB->id, 'key' => 'metaB']);
        $tagC = factory(Tag::class)->create();
        factory(TagMeta::class)->create(['tag_id' => $tagC->id, 'key' => 'metaC']);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], [], ['meta.key']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applySortingToQuery($query, $request);

        $tags = $query->get();

        $this->assertEquals($tagA->id, $tags[0]->id);
        $this->assertEquals($tagB->id, $tags[1]->id);
        $this->assertEquals($tagC->id, $tags[2]->id);
    }

    /** @test */
    public function desc_sorting_based_on_relation_fields()
    {
        $request = $this->makeRequestWithSort([
            ['field' => 'meta.key', 'direction' => 'desc']
        ]);

        $tagA = factory(Tag::class)->create();
        factory(TagMeta::class)->create(['tag_id' => $tagA->id, 'key' => 'metaA']);
        $tagB = factory(Tag::class)->create();
        factory(TagMeta::class)->create(['tag_id' => $tagB->id, 'key' => 'metaB']);
        $tagC = factory(Tag::class)->create();
        factory(TagMeta::class)->create(['tag_id' => $tagC->id, 'key' => 'metaC']);

        $query = Tag::query();

        $queryBuilder = new QueryBuilder(
            Tag::class,
            new ParamsValidator([], [], ['meta.key']),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $queryBuilder->applySortingToQuery($query, $request);

        $tags = $query->get();

        $this->assertEquals($tagC->id, $tags[0]->id);
        $this->assertEquals($tagB->id, $tags[1]->id);
        $this->assertEquals($tagA->id, $tags[2]->id);
    }

    /** @test */
    public function soft_deletes_query_constraints_are_not_applied_if_model_is_not_soft_deletable()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/tags', [ControllerStub::class, 'index']);
        });

        $queryMock = Mockery::mock(Tag::query())->makePartial();
        $queryMock->shouldNotReceive('withTrashed');
        $queryMock->shouldNotReceive('onlyTrashed');

        $queryBuilder = new QueryBuilder(
            Tag::class,
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
            return new Route('GET', '/api/history', [ControllerStub::class, 'index']);
        });
        $request->query->set('with_trashed', true);

        $supplier = factory(Supplier::class)->create();
        $history = factory(History::class)->create(['supplier_id' => $supplier->id]);
        $softDeletedHistory = factory(History::class)->state('trashed')->create(['supplier_id' => $supplier->id]);

        $query = History::query();

        $queryBuilder = new QueryBuilder(
            History::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $this->assertTrue($queryBuilder->applySoftDeletesToQuery($query, $request));

        $historyEntities = $query->get();

        $this->assertCount(2, $historyEntities);
        $this->assertTrue($historyEntities->contains('id', $history->id));
        $this->assertTrue($historyEntities->contains('id', $softDeletedHistory->id));
    }

    /** @test */
    public function only_trashed_models_are_returned_when_requested()
    {
        $request = new Request();
        $request->setRouteResolver(function () {
            return new Route('GET', '/api/history', [ControllerStub::class, 'index']);
        });
        $request->query->set('only_trashed', true);

        $supplier = factory(Supplier::class)->create();
        $history = factory(History::class)->create(['supplier_id' => $supplier->id]);
        $softDeletedHistory = factory(History::class)->state('trashed')->create(['supplier_id' => $supplier->id]);

        $query = History::query();

        $queryBuilder = new QueryBuilder(
            History::class,
            new ParamsValidator([], []),
            new RelationsResolver([], []),
            new SearchBuilder([])
        );
        $this->assertTrue($queryBuilder->applySoftDeletesToQuery($query, $request));

        $historyEntities = $query->get();

        $this->assertCount(1, $historyEntities);
        $this->assertFalse($historyEntities->contains('id', $history->id));
        $this->assertTrue($historyEntities->contains('id', $softDeletedHistory->id));
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
