<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Responses\Error;

use Orion\ValueObjects\Specs\Response;

class UnauthorizedResponse extends Response
{
    public $statusCode = 403;
    public $description = 'Unauthorized';
}
