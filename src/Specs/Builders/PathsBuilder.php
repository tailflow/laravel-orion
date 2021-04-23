<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Orion\Http\Controllers\Controller;
use Orion\Specs\ResourcesCacheStore;
use Orion\ValueObjects\Specs\Path;

class PathsBuilder
{
    /** @var ResourcesCacheStore */
    protected $resourcesCacheStore;

    /** @var Router */
    protected $router;

    public function __construct(ResourcesCacheStore $resourcesCacheStore, Router $router)
    {
        $this->resourcesCacheStore = $resourcesCacheStore;
        $this->router = $router;
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
                if (!in_array($operationName, ['index', 'search', 'store', 'show', 'update', 'destroy', 'restore'])) {
                    continue;
                }

                $route = $this->resolveRoute($resource->controller, $operationName);

                $operationBuilder = $this->resolveOperationBuilder($resource->controller, $operationName, $route);
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

    public function buildParameters(Route $route, string $controllerClass): array
    {
        $parameterNames = $route->parameterNames();
        /** @var Controller $controller */
        $controller = app()->make($controllerClass);
        /** @var Model $model */
        $model = app()->make($controller->resolveResourceModelClass());

        return collect($parameterNames)->map(
            function (string $parameterName) use ($route, $model) {
                return [
                    'schema' => [
                        'type' => $model->getKeyType() === 'int' ? 'integer' : $model->getKeyType(),
                    ],
                    'name' => $parameterName,
                    'in' => 'path',
                    'required' => strpos($route->uri(), "{{$parameterName}?}") === false,
                ];
            }
        )->toArray();
    }

    public function resolveRoute(string $controller, string $operationName): Route
    {
        return $this->router->getRoutes()->getByAction("{$controller}@{$operationName}");
    }

    /**
     * @param string $controller
     * @param string $operation
     * @param Route $route
     * @return OperationBuilder
     * @throws BindingResolutionException
     */
    public function resolveOperationBuilder(string $controller, string $operation, Route $route): OperationBuilder
    {
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\".ucfirst($operation).'OperationBuilder';

        return app()->makeWith(
            $operationClassName,
            [
                'controller' => $controller,
                'operation' => $operation,
                'route' => $route,
            ]
        );
    }
}
