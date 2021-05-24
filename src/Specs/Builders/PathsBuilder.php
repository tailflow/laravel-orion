<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Orion\Concerns\InteractsWithSoftDeletes;
use Orion\Http\Controllers\Controller;
use Orion\Http\Controllers\RelationController;
use Orion\Specs\Factories\OperationBuilderFactory;
use Orion\Specs\Factories\RelationOperationBuilderFactory;
use Orion\Specs\ResourcesCacheStore;
use Orion\ValueObjects\RegisteredResource;
use Orion\ValueObjects\Specs\Path;

class PathsBuilder
{
    use InteractsWithSoftDeletes;

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
            foreach ($resource->operations as $operationName) {
                $route = $this->resolveRoute($resource->controller, $operationName);

                // TODO: batch operations
                if (in_array($operationName, ['index', 'search', 'store', 'show', 'update', 'destroy', 'restore'])) {
                    $operationBuilder = $this->resolveStandardOperationBuilder($resource, $operationName, $route);
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
        $pathParameters = $this->buildPathParameters($route, $controllerClass);
        $queryParameters = $this->buildQueryParameters($route, $controllerClass);

        return collect($pathParameters)->merge($queryParameters)->toArray();
    }

    /**
     * @param Route $route
     * @param string $controllerClass
     * @return array
     * @throws BindingResolutionException
     */
    protected function buildPathParameters(Route $route, string $controllerClass): array
    {
        $parameterNames = $route->parameterNames();
        /** @var Controller $controller */
        $controller = app()->make($controllerClass);

        return collect($parameterNames)->map(
            function (string $parameterName, int $index) use ($route, $controller) {
                /** @var Model $model */
                if ($index === 0 && $controller instanceof RelationController) {
                    $model = app()->make($controller->getModel());
                } else {
                    $model = app()->make($controller->resolveResourceModelClass());
                }

                return $this->buildPathParameter($model, $parameterName, $route);
            }
        )->toArray();
    }

    protected function buildQueryParameters(Route $route, string $controllerClass): array
    {
        /** @var Controller $controller */
        $controller = app()->make($controllerClass);
        $softDeletes = $this->softDeletes($controller->resolveResourceModelClass());

        switch ($route->getActionMethod()) {
            case 'destroy':
                return $softDeletes ? [$this->buildQueryParameter('boolean', 'force')] : [];
            case 'index':
            case 'search':
            case 'show':
                return $softDeletes ? [
                    $this->buildQueryParameter('boolean', 'with_trashed'),
                    $this->buildQueryParameter('boolean', 'only_trashed')
                ] : [];
            default:
                return [];
        }
    }

    /**
     * @param Model $model
     * @param string $parameterName
     * @param Route $route
     * @return array
     */
    protected function buildPathParameter(Model $model, string $parameterName, Route $route): array
    {
        return [
            'schema' => [
                'type' => $model->getKeyType() === 'int' ? 'integer' : $model->getKeyType(),
            ],
            'name' => $parameterName,
            'in' => 'path',
            'required' => strpos($route->uri(), "{{$parameterName}?}") === false,
        ];
    }

    protected function buildQueryParameter(string $type, string $name): array
    {
        return [
            'schema' => [
                'type' => $type,
            ],
            'name' => $name,
            'in' => 'query',
            'required' => false,
        ];
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
