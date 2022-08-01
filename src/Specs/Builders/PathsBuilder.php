<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
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

    /**
     * PathsBuilder constructor.
     *
     * @param ResourcesCacheStore $resourcesCacheStore
     * @param Router $router
     * @param OperationBuilderFactory $operationBuilderFactory
     * @param RelationOperationBuilderFactory $relationOperationBuilderFactory
     */
    public function __construct(
        ResourcesCacheStore $resourcesCacheStore,
        Router $router,
        OperationBuilderFactory $operationBuilderFactory,
        RelationOperationBuilderFactory $relationOperationBuilderFactory
    ) {
        $this->resourcesCacheStore = $resourcesCacheStore;
        $this->router = $router;
        $this->operationBuilderFactory = $operationBuilderFactory;
        $this->relationOperationBuilderFactory = $relationOperationBuilderFactory;
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
            $operations = $this->resolveOperations($resource);

            foreach ($operations as $operationName) {
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
                    $operationBuilder = $this->resolveCustomOperationBuilder(
                        $resource,
                        $operationName,
                        $route
                    );
                }

                $operation = $operationBuilder->build();

                if (!$path = $paths->where('path', $route->uri())->first()) {
                    $path = new Path($route->uri());

                    $paths->put(Str::start($path->path, '/'), $path);
                }

                $path->operations->put(strtolower($operation->method), $operation);
            }
        }

        return $paths->toArray();
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
     * @return array
     */
    protected function resolveOperations(RegisteredResource $resource): array
    {
        $operations = collect($resource->operations);

        $routes = $this->router->getRoutes()->getRoutes();

        foreach ($routes as $route) {
            /** @var Route $route */
            if (get_class($route->getController()) === $resource->controller) {
                $operations->push($route->getActionMethod());
            }
        }

        return $operations->unique()->values()->toArray();
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
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\".ucfirst($operation).'OperationBuilder';

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
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\Batch\\".ucfirst($operation).'OperationBuilder';

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
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\Relations\\OneToMany\\".ucfirst(
                $operation
            ).'OperationBuilder';

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
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\Relations\\ManyToMany\\".ucfirst(
                $operation
            ).'OperationBuilder';

        return $this->relationOperationBuilderFactory->make($operationClassName, $resource, $operation, $route);
    }

    /**
     * @param RegisteredResource $resource
     * @param string $operation
     * @param Route $route
     * @return RelationOperationBuilder
     * @throws BindingResolutionException
     */
    protected function resolveCustomOperationBuilder(
        RegisteredResource $resource,
        string $operation,
        Route $route
    ): OperationBuilder {
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\CustomOperationBuilder";

        return $this->operationBuilderFactory->make($operationClassName, $resource, $operation, $route);
    }
}
