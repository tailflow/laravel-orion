<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Responses\Error;

use Orion\ValueObjects\Specs\Response;

class ValidationErrorResponse extends Response
{
    public $statusCode = 422;
    public $description = 'Validation error';
}
