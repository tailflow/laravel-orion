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

        $operationsOrder = [
            'store', 'index', 'search', 'show', 'update', 'destroy', 'restore',
            'batchStore', 'batchUpdate', 'batchDestroy', 'batchRestore',
            'associate', 'dissociate',
            'attach', 'detach', 'sync', 'toggle', 'updatePivot',
        ];

        foreach ($resources as $resource) {
            $operations = array_merge(
                array_intersect($operationsOrder, $resource->operations),
                array_diff($resource->operations, $operationsOrder)
            );

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
                    continue;
                }

                $operation = $operationBuilder->build();

                $formattedPath =  str_replace('?', '', $route->uri());

                if (!$path = $paths->where('path', $formattedPath)->first()) {
                    $path = app()->make(Path::class, ['path' => $formattedPath]);

                    $paths->put(Str::start($formattedPath, '/'), $path);
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
