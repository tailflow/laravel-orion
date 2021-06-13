<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\RequestBody;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Http\Controllers\BaseController;

abstract class SearchPartialBuilder
{
    /** @var BaseController */
    protected $controller;

    /**
     * SearchPartialBuilder constructor.
     *
     * @param string $controller
     * @throws BindingResolutionException
     */
    public function __construct(string $controller)
    {
        $this->controller = app()->make($controller);
    }

    abstract public function build(): ?array;
}
