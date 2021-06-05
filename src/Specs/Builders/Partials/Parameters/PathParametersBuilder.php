<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\Parameters;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Orion\Http\Controllers\Controller;
use Orion\Http\Controllers\RelationController;

class PathParametersBuilder
{
    /**
     * @param Route $route
     * @param string $controllerClass
     * @return array
     * @throws BindingResolutionException
     */
    public function build(Route $route, string $controllerClass): array
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
}
