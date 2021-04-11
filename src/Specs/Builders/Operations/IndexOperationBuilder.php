<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Responses\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\UnauthorizedResponse;

class IndexOperationBuilder extends OperationBuilder
{
    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Get a list of {$this->resolveResourceName(true)}";

        return $operation;
    }

    protected function resolveResponses(): array
    {
        return [
            new UnauthenticatedResponse(),
            new UnauthorizedResponse(),
        ];
    }
}
