<?php

namespace Orion\Tests\Unit\Http\Controllers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Orion\Drivers\Standard\ComponentsResolver;
use Orion\Drivers\Standard\Paginator;
use Orion\Drivers\Standard\ParamsValidator;
use Orion\Drivers\Standard\QueryBuilder;
use Orion\Drivers\Standard\RelationsResolver;
use Orion\Drivers\Standard\SearchBuilder;
use Orion\Exceptions\BindingException;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Unit\Http\Controllers\Stubs\RelationControllerStub;
use Orion\Tests\Unit\Http\Controllers\Stubs\RelationControllerStubWithoutRelation;
use Orion\Tests\Unit\TestCase;

class RelationControllerTest extends TestCase
{
    /** @test */
    public function binding_exception_is_thrown_if_model_is_not_set()
    {
        $this->expectException(BindingException::class);
        $this->expectExceptionMessage('Relation is not defined for '.RelationControllerStubWithoutRelation::class);

        $stub = new RelationControllerStubWithoutRelation();
    }

    /** @test */
    public function dependencies_are_resolved_correctly()
    {
        $fakeComponentsResolver = new ComponentsResolver(Post::class);
        $fakeParamsValidator = new ParamsValidator();
        $fakeRelationsResolver = new RelationsResolver([], []);
        $fakePaginator = new Paginator(15);
        $fakeSearchBuilder = new SearchBuilder([]);
        $fakeQueryBuilder = new QueryBuilder(Post::class, $fakeParamsValidator, $fakeRelationsResolver, $fakeSearchBuilder);
        $fakeRelationQueryBuilder = new QueryBuilder(User::class, $fakeParamsValidator, $fakeRelationsResolver, $fakeSearchBuilder);

        App::shouldReceive('makeWith')->with(\Orion\Contracts\ComponentsResolver::class, [
            'resourceModelClass' => User::class
        ])->once()->andReturn($fakeComponentsResolver);

        App::shouldReceive('makeWith')->with(\Orion\Contracts\ParamsValidator::class, [
            'exposedScopes' => ['testScope'],
            'filterableBy' => ['test_filterable_field'],
            'sortableBy' => ['test_sortable_field']
        ])->once()->andReturn($fakeParamsValidator);

        App::shouldReceive('makeWith')->with(\Orion\Contracts\RelationsResolver::class, [
            'includableRelations' => ['testRelation'],
            'alwaysIncludedRelations' => ['testAlwaysIncludedRelation'],
        ])->once()->andReturn($fakeRelationsResolver);

        App::shouldReceive('makeWith')->with(\Orion\Contracts\Paginator::class, [
            'defaultLimit' => 15,
        ])->once()->andReturn($fakePaginator);

        App::shouldReceive('makeWith')->with(\Orion\Contracts\SearchBuilder::class, [
            'searchableBy' => ['test_searchable_field']
        ])->once()->andReturn($fakeSearchBuilder);

        App::shouldReceive('makeWith')->with(\Orion\Contracts\QueryBuilder::class, [
            'resourceModelClass' => Post::class,
            'paramsValidator' => $fakeParamsValidator,
            'relationsResolver' => $fakeRelationsResolver,
            'searchBuilder' => $fakeSearchBuilder,
            'intermediateMode' => true
        ])->once()->andReturn($fakeQueryBuilder);

        App::shouldReceive('makeWith')->with(\Orion\Contracts\QueryBuilder::class, [
            'resourceModelClass' => User::class,
            'paramsValidator' => $fakeParamsValidator,
            'relationsResolver' => $fakeRelationsResolver,
            'searchBuilder' => $fakeSearchBuilder
        ])->once()->andReturn($fakeRelationQueryBuilder);

        $stub = new RelationControllerStub();
        $this->assertEquals($fakeComponentsResolver, $stub->getComponentsResolver());
        $this->assertEquals($fakeParamsValidator, $stub->getParamsValidator());
        $this->assertEquals($fakeRelationsResolver, $stub->getRelationsResolver());
        $this->assertEquals($fakePaginator, $stub->getPaginator());
        $this->assertEquals($fakeSearchBuilder, $stub->getSearchBuilder());
        $this->assertEquals($fakeQueryBuilder, $stub->getQueryBuilder());
        $this->assertEquals($fakeRelationQueryBuilder, $stub->getRelationQueryBuilder());
    }

    /** @test */
    public function creating_new_relation_query()
    {
        $parentEntity = new Post();
        $stub = new RelationControllerStub();

        $newRelationQuery = $stub->newRelationQuery($parentEntity);

        $this->assertInstanceOf(Relation::class, $newRelationQuery);
        $this->assertInstanceOf(User::class, $newRelationQuery->getModel());
    }
}
