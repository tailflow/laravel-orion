<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Responses\PaginatedCollectionResponse;
use Orion\ValueObjects\Specs\Responses\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\UnauthorizedResponse;

class SearchOperationBuilder extends OperationBuilder
{

    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Search for {$this->resolveResourceName(true)}";

        return $operation;
    }

    protected function resolveResponses(): array
    {
        return [
            new PaginatedCollectionResponse($this->resolveResourceComponentBaseName()),
            new UnauthenticatedResponse(),
            new UnauthorizedResponse(),
        ];
    }
}
