<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Responses\ResourceNotFoundResponse;
use Orion\ValueObjects\Specs\Responses\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\UnauthorizedResponse;

abstract class OperationBuilder
{
    /**
     * @var string
     */
    protected $controller;
    /**
     * @var string
     */
    protected $operation;
    /**
     * @var Route
     */
    protected $route;

    public function __construct(string $controller, string $operation, Route $route)
    {
        $this->controller = $controller;
        $this->operation = $operation;
        $this->route = $route;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    abstract public function build(): Operation;

    protected function makeBaseOperation(): Operation
    {
        $operation = new Operation();
        $operation->id = $this->route->getName();
        $operation->method = Arr::first($this->route->methods());
        $operation->responses = $this->resolveResponses();

        return $operation;
    }

    protected function resolveResponses(): array
    {
        return [
            new UnauthenticatedResponse(),
            new UnauthorizedResponse(),
            new ResourceNotFoundResponse(),
        ];
    }

    protected function resolveResourceName(bool $pluralize = false): string
    {
        $resourceModelClass = app()->make($this->controller)->resolveResourceModelClass();
        /** @var Model $resourceModel */
        $resourceModel = app()->make($resourceModelClass);

        $resourceName = Str::lower(Str::replaceArray('_', [' '], $resourceModel->getTable()));

        if (!$pluralize) {
            return Str::singular($resourceName);
        }

        return $resourceName;
    }
}
