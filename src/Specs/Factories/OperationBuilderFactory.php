<?php

declare(strict_types=1);

namespace Orion\Specs\Factories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\RegisteredResource;

class OperationBuilderFactory
{
    /**
     * @param string $operationBuilderClass
     * @param RegisteredResource $resource
     * @param string $operation
     * @param Route $route
     * @return OperationBuilder
     * @throws BindingResolutionException
     */
    public function make(string $operationBuilderClass, RegisteredResource $resource, string $operation, Route $route): OperationBuilder
    {
        return app()->makeWith(
            $operationBuilderClass,
            [
                'resource' => $resource,
                'operation' => $operation,
                'route' => $route,
            ]
        );
    }
}
