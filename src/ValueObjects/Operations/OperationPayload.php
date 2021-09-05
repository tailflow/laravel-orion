<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Operations;

use Orion\Http\Requests\Request;

class OperationPayload
{
    public Request $request;
    public array $requestedRelations;

    public function __construct(Request $request, array $requestedRelations = [])
    {
        $this->request = $request;
        $this->requestedRelations = $requestedRelations;
    }
}
