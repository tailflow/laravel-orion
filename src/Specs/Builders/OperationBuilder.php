<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
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
     * @var Router
     */
    protected $router;
    /**
     * @var Route
     */
    protected $route;

    public function __construct(string $controller, string $operation, Router $router)
    {
        $this->controller = $controller;
        $this->operation = $operation;
        $this->router = $router;
        $this->route = $this->resolveRoute();
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

    protected function resolveRoute(): Route
    {
        return $this->router->getRoutes()->getByAction("{$this->controller}@{$this->operation}");
    }

    protected function makeBaseOperation(): Operation
    {
        $operation = new Operation();
        $operation->path = $this->route->uri();

        return $operation;
    }
}
