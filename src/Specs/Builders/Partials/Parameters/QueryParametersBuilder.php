<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\Parameters;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Orion\Concerns\InteractsWithSoftDeletes;
use Orion\Http\Controllers\Controller;

class QueryParametersBuilder
{
    use InteractsWithSoftDeletes;

    /**
     * @param Route $route
     * @param string $controllerClass
     * @return array
     * @throws BindingResolutionException
     */
    public function build(Route $route, string $controllerClass): array
    {
        /** @var Controller $controller */
        $controller = app()->make($controllerClass);

        $softDeletes = $this->softDeletes($controller->resolveResourceModelClass());

        $includes = array_merge($controller->alwaysIncludes(), $controller->includes());
        $hasIncludes = (bool)count($includes);

        switch ($route->getActionMethod()) {
            case 'destroy':
            case 'batchDestroy':
                $parameters = [];

                if ($softDeletes) {
                    $parameters[] = $this->buildQueryParameter('boolean', 'force');
                }

                if ($hasIncludes) {
                    $parameters[] = $this->buildQueryParameter('string', 'include', $includes);
                }

                return $parameters;
            case 'index':
            case 'search':
            case 'show':
                $parameters = [];

                if ($softDeletes) {
                    $parameters[] = $this->buildQueryParameter('boolean', 'with_trashed');
                    $parameters[] = $this->buildQueryParameter('boolean', 'only_trashed');
                }

                if ($hasIncludes) {
                    $parameters[] = $this->buildQueryParameter('string', 'include', $includes);
                }

                return $parameters;
            default:
                return $hasIncludes ? [
                    $this->buildQueryParameter('string', 'include', $includes)
                ] : [];
        }
    }

    protected function buildQueryParameter(string $type, string $name, array $enum = []): array
    {
        $descriptor = [
            'schema' => [
                'type' => $type,
            ],
            'name' => $name,
            'in' => 'query'
        ];

        if (count($enum)) {
            $descriptor['schema']['enum'] = $enum;
        }

        return $descriptor;
    }
}
