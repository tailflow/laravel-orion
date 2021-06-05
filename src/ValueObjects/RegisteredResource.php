<?php

declare(strict_types=1);

namespace Orion\ValueObjects;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Http\Controllers\Controller;
use Str;

class RegisteredResource
{
    /** @var string */
    public $controller;
    /** @var string[] */
    public $operations;
    /** @var string */
    public $tag;

    public function __construct(string $controller, array $operations)
    {
        $this->controller = $controller;
        $this->operations = $operations;
        $this->tag = Str::title(
            str_replace(
                '_',
                ' ',
                Str::snake(str_replace('Controller', '', class_basename($controller)))
            )
        );
    }

    /**
     * @return string
     * @throws BindingResolutionException
     */
    public function getKeyType(): string
    {
        /** @var Controller $controller */
        $controller = app()->make($this->controller);

        $model = app()->make($controller->resolveResourceModelClass());

        return $model->getKeyType() === 'int' ? 'integer' : $model->getKeyType();
    }
}
