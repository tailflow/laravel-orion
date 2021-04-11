<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Responses;

use Orion\ValueObjects\Specs\Response;

class UnauthenticatedResponse extends Response
{
    public $statusCode = 401;
    public $description = 'Unauthenticated';
}
