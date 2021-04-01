<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Orion\Specs\ResourcesCacheStore;
use Orion\ValueObjects\Specs\Path;

class PathsBuilder
{
    /** @var ResourcesCacheStore */
    protected $resourcesCacheStore;

    public function __construct(ResourcesCacheStore $resourcesCacheStore)
    {
        $this->resourcesCacheStore = $resourcesCacheStore;
    }

    /**
     * @return array
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

                $operationBuilder = $this->resolveOperationBuilder($resource->controller, $operationName);
                $operation = $operationBuilder->build();

                if (!$path = $paths->where('path', $operation->path)->first()) {
                    $path = new Path($operation->path);
                    $paths->push($path);
                }

                /** @var Path $path */
                $path->operations->push($operation);
            }
        }

        return $paths->mapWithKeys(
            function (Path $path) {
                return [$path->path => $path->toArray()];
            }
        )->toArray();
    }

    /**
     * @param string $controller
     * @param string $operation
     * @return OperationBuilder
     */
    public function resolveOperationBuilder(string $controller, string $operation): OperationBuilder
    {
        $operationClassName = "Orion\\Specs\\Builders\\Operations\\".ucfirst($operation).'OperationBuilder';

        return app()->makeWith(
            $operationClassName,
            [
                'controller' => $controller,
                'operation' => $operation,
            ]
        );
    }
}
