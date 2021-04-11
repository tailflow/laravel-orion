<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Orion\ValueObjects\Specs\Operation;

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

        return $operation;
    }
}
