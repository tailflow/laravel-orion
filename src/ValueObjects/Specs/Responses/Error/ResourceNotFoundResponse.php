<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Responses\Error;

use Orion\ValueObjects\Specs\Response;

class ResourceNotFoundResponse extends Response
{
    public $statusCode = 404;
    public $description = 'Resource not found';
}
