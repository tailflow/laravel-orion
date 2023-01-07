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
        $hasIncludes = (bool) count($includes);

        $aggregates = $controller->aggregates();
        $hasAggregates = (bool) count($aggregates);

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

                if ($hasAggregates) {
                    $parameters[] = $this->buildAggregatesQueryParameters($aggregates);
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

                if ($hasAggregates) {
                    $parameters[] = $this->buildAggregatesQueryParameters($aggregates);
                }


                return $parameters;
            default:
                $parameters = [];

                if ($hasIncludes) {
                    $parameters[] = $this->buildQueryParameter('string', 'include', $includes);
                }

                if ($hasAggregates) {
                    $parameters = array_merge(
                        $parameters, $this->buildAggregatesQueryParameters($aggregates)
                    );
                }

                return $parameters;
        }
    }

    protected function buildQueryParameter(string $type, string $name, array $enum = []): array
    {
        $descriptor = [
            'schema' => [
                'type' => $type,
            ],
            'name' => $name,
            'in' => 'query',
        ];

        if (count($enum)) {
            $descriptor['schema']['enum'] = $enum;
        }

        return $descriptor;
    }

    protected function buildAggregatesQueryParameters(array $enum = []): array
    {
        $queryParameters = [];
        $queryParameters[] = $this->buildQueryParameter('string', 'with_count', $enum);
        $queryParameters[] = $this->buildQueryParameter('string', 'with_exists', $enum);
        $queryParameters[] = $this->buildQueryParameter('string', 'with_avg', $enum);
        $queryParameters[] = $this->buildQueryParameter('string', 'with_sum', $enum);
        $queryParameters[] = $this->buildQueryParameter('string', 'with_min', $enum);
        $queryParameters[] = $this->buildQueryParameter('string', 'with_max', $enum);

        return $queryParameters;
    }
}
