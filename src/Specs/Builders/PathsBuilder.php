<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Orion\Specs\Builders\Partials\Parameters\PathParametersBuilder;
use Orion\Specs\Builders\Partials\Parameters\QueryParametersBuilder;
use Orion\Specs\Factories\OperationBuilderFactory;
use Orion\Specs\Factories\RelationOperationBuilderFactory;
use Orion\Specs\ResourcesCacheStore;
use Orion\ValueObjects\RegisteredResource;
use Orion\ValueObjects\Specs\Path;

class PathsBuilder
{
    /** @var ResourcesCacheStore */
    protected $resourcesCacheStore;

    /** @var Router */
    protected $router;

    /** @var OperationBuilderFactory */
    protected $operationBuilderFactory;

    /** @var RelationOperationBuilderFactory */
    protected $relationOperationBuilderFactory;

    /** @var PathParametersBuilder */
    protected $pathParametersBuilder;

    /** @var QueryParametersBuilder */
    protected $queryParametersBuilder;

    /**
     * PathsBuilder constructor.
     *
     * @param ResourcesCacheStore $resourcesCacheStore
     * @param Router $router
     * @param OperationBuilderFactory $operationBuilderFactory
     * @param RelationOperationBuilderFactory $relationOperationBuilderFactory
     * @param PathParametersBuilder $pathParametersBuilder
     * @param QueryParametersBuilder $queryParametersBuilder
     */
    public function __construct(
        ResourcesCacheStore $resourcesCacheStore,
        Router $router,
        OperationBuilderFactory $operationBuilderFactory,
        RelationOperationBuilderFactory $relationOperationBuilderFactory,
        PathParametersBuilder $pathParametersBuilder,
        QueryParametersBuilder $queryParametersBuilder
    ) {
        $this->resourcesCacheStore = $resourcesCacheStore;
        $this->router = $router;
        $this->operationBuilderFactory = $operationBuilderFactory;
        $this->relationOperationBuilderFactory = $relationOperationBuilderFactory;
        $this->queryParametersBuilder = $queryParametersBuilder;
        $this->pathParametersBuilder = $pathParametersBuilder;
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    public function build(): array
    {
        $resources = $this->resourcesCacheStore->getResources();
        $paths = collect([]);

        foreach ($resources as $resource) {
            foreach ($resource->operations as $operationName) {
                $route = $this->resolveRoute($resource->controller, $operationName);

                if (in_array($operationName, ['index', 'search', 'store', 'show', 'update', 'destroy', 'restore'])) {
                    $operationBuilder = $this->resolveStandardOperationBuilder($resource, $operationName, $route);
                } elseif (in_array($operationName, ['batchStore', 'batchUpdate', 'batchDestroy', 'batchRestore'])) {
                    $operationBuilder = $this->resolveStandardBatchOperationBuilder($resource, $operationName, $route);
                } elseif (in_array($operationName, ['associate', 'dissociate'])) {
                    $operationBuilder = $this->resolveRelationOneToManyOperationBuilder(
                        $resource,
                        $operationName,
                        $route
                    );
                } elseif (in_array($operationName, ['attach', 'detach', 'sync', 'toggle', 'updatePivot'])) {
                    $operationBuilder = $this->resolveRelationManyToManyOperationBuilder(
                        $resource,
                        $operationName,
                        $route
                    );
                } else {
                    continue;
                }

                $operation = $operationBuilder->build();

                if (!$path = $paths->where('path', $route->uri())->first()) {
                    $path = new Path($route->uri());
                    $path->parameters = $this->buildParameters($route, $resource->controller);

                    $paths->put($path->path, $path);
                }

                $path->operations->put($operation->method, $operation);
            }
        }

        return $paths->toArray();
    }

    /**
     * @param Route $route
     * @param string $controllerClass
     * @return array
     * @throws BindingResolutionException
     */
    protected function buildParameters(Route $route, string $controllerClass): array
    {
        $pathParameters = $this->pathParametersBuilder->build($route, $controllerClass);
        $queryParameters = $this->queryParametersBuilder->build($route, $controllerClass);

        return collect($pathParameters)->merge($queryParameters)->toArray();
    }

    /**
     * @param string $controller
     * @param string $operationName
     * @return Route
     */
    protected function resolveRoute(string $controller, string $operationName): Route
    {
        return $this->router->getRoutes()->getByAction("{$controller}@{$operationName}");
    }

    /**
     * @param RegisteredResource $resource
     * @param string $operation
     * @param Route $route
     * @return OperationBuilder
     * @throws BindingResolutionException
     */
    protected function resolveStandardOperationBuilder(
        RegisteredResource $resource,
        string $operation,
        Route $route
    ): OperationBuilder {
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\" . ucfirst($operation) . 'OperationBuilder';

        return $this->operationBuilderFactory->make($operationClassName, $resource, $operation, $route);
    }

    /**
     * @param RegisteredResource $resource
     * @param string $operation
     * @param Route $route
     * @return OperationBuilder
     * @throws BindingResolutionException
     */
    protected function resolveStandardBatchOperationBuilder(
        RegisteredResource $resource,
        string $operation,
        Route $route
    ): OperationBuilder {
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\Batch\\" . ucfirst($operation) . 'OperationBuilder';

        return $this->operationBuilderFactory->make($operationClassName, $resource, $operation, $route);
    }

    /**
     * @param RegisteredResource $resource
     * @param string $operation
     * @param Route $route
     * @return RelationOperationBuilder
     * @throws BindingResolutionException
     */
    protected function resolveRelationOneToManyOperationBuilder(
        RegisteredResource $resource,
        string $operation,
        Route $route
    ): RelationOperationBuilder {
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\Relations\\OneToMany\\" . ucfirst(
                $operation
            ) . 'OperationBuilder';

        return $this->relationOperationBuilderFactory->make($operationClassName, $resource, $operation, $route);
    }

    /**
     * @param RegisteredResource $resource
     * @param string $operation
     * @param Route $route
     * @return RelationOperationBuilder
     * @throws BindingResolutionException
     */
    protected function resolveRelationManyToManyOperationBuilder(
        RegisteredResource $resource,
        string $operation,
        Route $route
    ): RelationOperationBuilder {
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\Relations\\ManyToMany\\" . ucfirst(
                $operation
            ) . 'OperationBuilder';

        return $this->relationOperationBuilderFactory->make($operationClassName, $resource, $operation, $route);
    }
}
