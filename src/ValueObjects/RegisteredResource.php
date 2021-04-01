<?php

declare(strict_types=1);

namespace Orion\ValueObjects;

class RegisteredResource
{
    /** @var string */
    public $controller;
    /** @var string[] */
    public $operations;

    public function __construct(string $controller, array $operations)
    {
        $this->controller = $controller;
        $this->operations = $operations;
    }

}
