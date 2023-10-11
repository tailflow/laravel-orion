<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Http\Controllers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Orion\Drivers\Standard\AppendsResolver;
use Orion\Drivers\Standard\ComponentsResolver;
use Orion\Drivers\Standard\Paginator;
use Orion\Drivers\Standard\ParamsValidator;
use Orion\Drivers\Standard\QueryBuilder;
use Orion\Drivers\Standard\RelationsResolver;
use Orion\Drivers\Standard\SearchBuilder;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Unit\Http\Controllers\Stubs\RelationControllerStub;
use Orion\Tests\Unit\TestCase;

class RelationControllerTest extends TestCase
{

    /** @test */
    public function dependencies_are_resolved_correctly(): void
    {
        $fakeComponentsResolver = new ComponentsResolver(User::class);
        $fakeParentComponentsResolver = new ComponentsResolver(Post::class);
        $fakeParamsValidator = new ParamsValidator();
        $fakeRelationsResolver = new RelationsResolver([], []);
        $fakeAppendsResolver = new AppendsResolver([], []);
        $fakePaginator = new Paginator(15, null);
        $fakeSearchBuilder = new SearchBuilder([]);
        $fakeQueryBuilder = new QueryBuilder(Post::class, $fakeParamsValidator, $fakeRelationsResolver, $fakeSearchBuilder);
        $fakeRelationQueryBuilder = new QueryBuilder(User::class, $fakeParamsValidator, $fakeRelationsResolver, $fakeSearchBuilder);

        App::shouldReceive('makeWith')->with(
            \Orion\Contracts\ComponentsResolver::class,
            [
                'resourceModelClass' => User::class,
            ]
        )->once()->andReturn($fakeComponentsResolver);

        App::shouldReceive('makeWith')->with(
            \Orion\Contracts\ComponentsResolver::class,
            [
                'resourceModelClass' => Post::class,
            ]
        )->once()->andReturn($fakeParentComponentsResolver);

        App::shouldReceive('makeWith')->with(
            \Orion\Contracts\ParamsValidator::class,
            [
                'exposedScopes' => ['testScope'],
                'filterableBy' => ['test_filterable_field'],
                'sortableBy' => ['test_sortable_field'],
                'aggregatableBy' => ['test_aggregatable_field'],
                'includableBy' => ['testRelation', 'testAlwaysIncludedRelation'],
            ]
        )->once()->andReturn($fakeParamsValidator);

        App::shouldReceive('makeWith')->with(
            \Orion\Contracts\RelationsResolver::class,
            [
                'includableRelations' => ['testRelation'],
                'alwaysIncludedRelations' => ['testAlwaysIncludedRelation'],
            ]
        )->once()->andReturn($fakeRelationsResolver);

        App::shouldReceive('makeWith')->with(
            \Orion\Contracts\AppendsResolver::class,
            [
                'appends' => ['testAppends'],
                'alwaysAppends' => ['testAlwaysAppends'],
            ]
        )->once()->andReturn($fakeAppendsResolver);

        App::shouldReceive('makeWith')->with(
            \Orion\Contracts\Paginator::class,
            [
                'defaultLimit' => 15,
                'maxLimit' => null,
            ]
        )->once()->andReturn($fakePaginator);

        App::shouldReceive('makeWith')->with(
            \Orion\Contracts\SearchBuilder::class,
            [
                'searchableBy' => ['test_searchable_field'],
            ]
        )->once()->andReturn($fakeSearchBuilder);

        App::shouldReceive('makeWith')->with(
            \Orion\Contracts\QueryBuilder::class,
            [
                'resourceModelClass' => Post::class,
                'paramsValidator' => $fakeParamsValidator,
                'relationsResolver' => $fakeRelationsResolver,
                'searchBuilder' => $fakeSearchBuilder,
                'intermediateMode' => true,
            ]
        )->once()->andReturn($fakeQueryBuilder);

        App::shouldReceive('makeWith')->with(
            \Orion\Contracts\QueryBuilder::class,
            [
                'resourceModelClass' => User::class,
                'paramsValidator' => $fakeParamsValidator,
                'relationsResolver' => $fakeRelationsResolver,
                'searchBuilder' => $fakeSearchBuilder,
            ]
        )->once()->andReturn($fakeRelationQueryBuilder);

        $stub = new RelationControllerStub();
        $this->assertEquals($fakeComponentsResolver, $stub->getComponentsResolver());
        $this->assertEquals($fakeParentComponentsResolver, $stub->getParentComponentsResolver());
        $this->assertEquals($fakeParamsValidator, $stub->getParamsValidator());
        $this->assertEquals($fakeRelationsResolver, $stub->getRelationsResolver());
        $this->assertEquals($fakeAppendsResolver, $stub->getAppendsResolver());
        $this->assertEquals($fakePaginator, $stub->getPaginator());
        $this->assertEquals($fakeSearchBuilder, $stub->getSearchBuilder());
        $this->assertEquals($fakeQueryBuilder, $stub->getQueryBuilder());
        $this->assertEquals($fakeRelationQueryBuilder, $stub->getRelationQueryBuilder());
    }

    /** @test */
    public function creating_new_relation_query(): void
    {
        $parentEntity = new Post();
        $stub = new RelationControllerStub();

        $newRelationQuery = $stub->newRelationQuery($parentEntity);

        $this->assertInstanceOf(Relation::class, $newRelationQuery);
        $this->assertInstanceOf(User::class, $newRelationQuery->getModel());
    }
}
