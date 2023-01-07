<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\Parameters;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Routing\Route;
use Orion\Http\Controllers\BaseController;
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
        /** @var BaseController $controller */
        $controller = app()->make($controllerClass);

        return collect($parameterNames)->map(
            function (string $parameterName, int $index) use ($route, $controller) {
                /** @var Model $model */
                if ($index === 0 && $controller instanceof RelationController) {
                    $model = app()->make($controller->getModel());
                } else {
                    $model = app()->make($controller->resolveResourceModelClass());
                }

                return $this->buildPathParameter($controller, $model, $parameterName, $index, $route);
            }
        )->toArray();
    }

    /**
     * @param BaseController $controller
     * @param Model $model
     * @param string $parameterName
     * @param int $index
     * @param Route $route
     * @return array
     */
    protected function buildPathParameter(
        BaseController $controller,
        Model $model,
        string $parameterName,
        int $index,
        Route $route
    ): array {
        $required = true;

        if ($controller instanceof RelationController) {
            $optionalInDefinition = strpos($route->uri, "{$parameterName}?");

            $relation = $controller->resolveRelation();

            if ($relation instanceof HasOne || $relation instanceof MorphOne) {
                $required = !$optionalInDefinition && $index === 0;
            }
        }

        return [
            'schema' => [
                'type' => $model->getKeyType() === 'int' ? 'integer' : $model->getKeyType(),
            ],
            'name' => $parameterName,
            'in' => 'path',
            'required' => $required,
        ];
    }
}
