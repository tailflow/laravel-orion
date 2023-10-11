<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Http\Controllers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Mockery;
use Orion\Drivers\Standard\AppendsResolver;
use Orion\Drivers\Standard\ComponentsResolver;
use Orion\Drivers\Standard\Paginator;
use Orion\Drivers\Standard\ParamsValidator;
use Orion\Drivers\Standard\QueryBuilder;
use Orion\Drivers\Standard\RelationsResolver;
use Orion\Drivers\Standard\SearchBuilder;
use Orion\Repositories\Repository;
use Orion\Repositories\BaseRepository;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleCollectionResource;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Repositories\PostBaseRepository;
use Orion\Tests\Unit\Http\Controllers\Stubs\BaseControllerStub;
use Orion\Tests\Unit\Http\Controllers\Stubs\BaseControllerStubWithoutComponents;
use Orion\Tests\Unit\Http\Controllers\Stubs\BaseControllerStubWithWhitelistedFieldsAndRelations;
use Orion\Tests\Unit\TestCase;

class BaseControllerTest extends TestCase
{

    /** @test */
    public function dependencies_are_resolved_correctly(): void
    {
        $fakeComponentsResolver = new ComponentsResolver(Post::class);
        $fakeParamsValidator = new ParamsValidator();
        $fakeRelationsResolver = new RelationsResolver([], []);
        $fakeAppendsResolver = new AppendsResolver([], []);
        $fakePaginator = new Paginator(15, null);
        $fakeSearchBuilder = new SearchBuilder([]);
        $fakeQueryBuilder = new QueryBuilder(Post::class, $fakeParamsValidator, $fakeRelationsResolver, $fakeSearchBuilder);

        App::shouldReceive('makeWith')->with(
            \Orion\Contracts\ComponentsResolver::class,
            [
                'resourceModelClass' => Post::class,
            ]
        )->once()->andReturn($fakeComponentsResolver);

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
                'intermediateMode' => false,
            ]
        )->once()->andReturn($fakeQueryBuilder);

        $stub = new BaseControllerStubWithWhitelistedFieldsAndRelations();
        $this->assertEquals($fakeComponentsResolver, $stub->getComponentsResolver());
        $this->assertEquals($fakeParamsValidator, $stub->getParamsValidator());
        $this->assertEquals($fakeRelationsResolver, $stub->getRelationsResolver());
        $this->assertEquals($fakeAppendsResolver, $stub->getAppendsResolver());
        $this->assertEquals($fakePaginator, $stub->getPaginator());
        $this->assertEquals($fakeSearchBuilder, $stub->getSearchBuilder());
        $this->assertEquals($fakeQueryBuilder, $stub->getQueryBuilder());
    }

    /** @test */
    public function using_predefined_components(): void
    {
        App::bind(
            \Orion\Contracts\ComponentsResolver::class,
            function () {
                $componentsResolverMock = Mockery::mock(ComponentsResolver::class, [Post::class])->makePartial();
                $componentsResolverMock->shouldReceive('resolveRepositoryClass')->never();
                $componentsResolverMock->shouldReceive('resolveRequestClass')->never();
                $componentsResolverMock->shouldReceive('resolveResourceClass')->never();
                $componentsResolverMock->shouldReceive('resolveCollectionResourceClass')->never();

                return $componentsResolverMock;
            }
        );

        $stub = new BaseControllerStub();
        $this->assertEquals(PostBaseRepository::class, $stub->getRepository());
        $this->assertEquals(PostRequest::class, $stub->getRequest());
        $this->assertEquals(SampleResource::class, $stub->getResource());
        $this->assertEquals(SampleCollectionResource::class, $stub->getCollectionResource());
    }

    /** @test */
    public function resolving_components(): void
    {
        App::bind(
            \Orion\Contracts\ComponentsResolver::class,
            function () {
                $componentsResolverMock = Mockery::mock(ComponentsResolver::class, [Post::class])->makePartial();
                $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->withNoArgs()->andReturn('testRequestClass');
                $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->withNoArgs()->andReturn('testResourceClass');
                $componentsResolverMock->shouldReceive('resolveCollectionResourceClass')->once()->withNoArgs()->andReturn('testCollectionResourceClass');

                return $componentsResolverMock;
            }
        );

        $stub = new BaseControllerStubWithoutComponents();
        $this->assertEquals(Repository::class, $stub->getRepository());
        $this->assertEquals('testRequestClass', $stub->getRequest());
        $this->assertEquals('testResourceClass', $stub->getResource());
        $this->assertEquals('testCollectionResourceClass', $stub->getCollectionResource());
    }

    /** @test */
    public function binding_components(): void
    {
        App::bind(
            \Orion\Contracts\ComponentsResolver::class,
            function () {
                $componentsResolverMock = Mockery::mock(ComponentsResolver::class, [Post::class])->makePartial();
                $componentsResolverMock->shouldReceive('bindRequestClass')->with(PostRequest::class)->once();

                return $componentsResolverMock;
            }
        );

        $stub = new BaseControllerStub();
    }

    /** @test */
    public function instantiating_components(): void
    {
        App::bind(
            \Orion\Contracts\ComponentsResolver::class,
            function () {
                $componentsResolverMock = Mockery::mock(ComponentsResolver::class, [Post::class])->makePartial();
                $componentsResolverMock->shouldReceive('instantiateRepository')->with(PostBaseRepository::class)->once();

                return $componentsResolverMock;
            }
        );

        $stub = new BaseControllerStub();
    }

    /**
     * @test
     * @throws BindingResolutionException
     */
    public function authorize(): void
    {
        $user = new User(['name' => 'test user']);
        $ability = 'create';
        $arguments = [User::class];

        $controllerMock = Mockery::mock(BaseControllerStub::class)->makePartial();
        $controllerMock->shouldReceive('resolveUser')->once()->withNoArgs()->andReturn($user);
        $controllerMock->shouldReceive('authorizeForUser')->once()->with($user, $ability, $arguments)->andReturn(true);

        $this->assertTrue($controllerMock->authorize($ability, $arguments));
    }

    /** @test */
    public function creating_new_model_query(): void
    {
        $stub = new BaseControllerStub();

        $newModelQuery = $stub->newModelQuery();

        $this->assertInstanceOf(Builder::class, $newModelQuery);
        $this->assertInstanceOf(Post::class, $newModelQuery->getModel());
    }

    /** @test */
    public function resolving_model_class(): void
    {
        $stub = new BaseControllerStub();

        $this->assertEquals(Post::class, $stub->model());
    }

    /** @test */
    public function resolving_user_with_api_guard(): void
    {
        $user = new User(['name' => 'test user']);
        $this->actingAs($user, 'api');

        $stub = new BaseControllerStub();
        $resolvedUser = $stub->resolveUser();

        $this->assertTrue($user->is($resolvedUser));
    }

    /** @test */
    public function resolving_user_with_other_guards(): void
    {
        $user = new User(['name' => 'test user']);
        $this->actingAs($user, 'web');

        $stub = new BaseControllerStub();
        $resolvedUser = $stub->resolveUser();

        $this->assertFalse($user->is($resolvedUser));
    }
}
