<?php

declare(strict_types=1);

namespace Orion\Specs\Factories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Orion\Specs\Builders\RelationOperationBuilder;
use Orion\ValueObjects\RegisteredResource;

class RelationOperationBuilderFactory
{
    /**
     * @param string $operationBuilderClass
     * @param RegisteredResource $resource
     * @param string $operation
     * @param Route $route
     * @return RelationOperationBuilder
     * @throws BindingResolutionException
     */
    public function make(string $operationBuilderClass, RegisteredResource $resource, string $operation, Route $route): RelationOperationBuilder
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
