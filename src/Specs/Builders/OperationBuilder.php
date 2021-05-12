<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Orion\ValueObjects\RegisteredResource;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Request;
use Orion\ValueObjects\Specs\Responses\Error\ResourceNotFoundResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthorizedResponse;

abstract class OperationBuilder
{
    /**
     * @var RegisteredResource
     */
    protected $resource;
    /**
     * @var string
     */
    protected $operation;
    /**
     * @var Route
     */
    protected $route;

    /**
     * OperationBuilder constructor.
     *
     * @param RegisteredResource $resource
     * @param string $operation
     * @param Route $route
     */
    public function __construct(RegisteredResource $resource, string $operation, Route $route)
    {
        $this->resource = $resource;
        $this->operation = $operation;
        $this->route = $route;
    }

    /**
     * @return RegisteredResource
     */
    public function getResource(): RegisteredResource
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @return Operation
     */
    abstract public function build(): Operation;

    /**
     * @return Request|null
     */
    protected function request(): ?Request
    {
        return null;
    }

    /**
     * @return array
     */
    protected function responses(): array
    {
        return [
            new UnauthenticatedResponse(),
            new UnauthorizedResponse(),
            new ResourceNotFoundResponse(),
        ];
    }

    /**
     * @return Operation
     */
    protected function makeBaseOperation(): Operation
    {
        $operation = new Operation();
        $operation->id = $this->route->getName();
        $operation->method = Arr::first($this->route->methods());
        $operation->request = $this->request();
        $operation->responses = $this->responses();
        $operation->tags = [$this->resource->tag];

        return $operation;
    }

    /**
     * @param bool $pluralize
     * @return string
     * @throws BindingResolutionException
     */
    protected function resolveResourceName(bool $pluralize = false): string
    {
        $resourceModelClass = app()->make($this->resource->controller)->resolveResourceModelClass();
        /** @var Model $resourceModel */
        $resourceModel = app()->make($resourceModelClass);

        $resourceName = Str::lower(Str::replaceArray('_', [' '], $resourceModel->getTable()));

        if (!$pluralize) {
            return Str::singular($resourceName);
        }

        return $resourceName;
    }

    /**
     * @return string
     * @throws BindingResolutionException
     */
    protected function resolveResourceComponentBaseName(): string
    {
        return class_basename(app()->make($this->resource->controller)->resolveResourceModelClass());
    }
}
