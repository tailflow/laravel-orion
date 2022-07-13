<?php

declare(strict_types=1);

namespace Orion\Http\Responses;

abstract class Response
{
    abstract public function getSchema(): array;
}
