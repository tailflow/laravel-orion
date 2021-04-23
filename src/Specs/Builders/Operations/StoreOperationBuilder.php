<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Responses\EntityResponse;
use Orion\ValueObjects\Specs\Responses\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\UnauthorizedResponse;
use Orion\ValueObjects\Specs\Responses\ValidationErrorResponse;

class StoreOperationBuilder extends OperationBuilder
{

    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Create {$this->resolveResourceName()}";

        return $operation;
    }

    protected function resolveResponses(): array
    {
        return [
            new EntityResponse($this->resolveResourceComponentBaseName(), 201),
            new UnauthenticatedResponse(),
            new UnauthorizedResponse(),
            new ValidationErrorResponse(),
        ];
    }
}
