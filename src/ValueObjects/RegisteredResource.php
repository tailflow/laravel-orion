<?php

declare(strict_types=1);

namespace Orion\ValueObjects;

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

}
