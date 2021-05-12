<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Responses\Error\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthorizedResponse;
use Orion\ValueObjects\Specs\Responses\Success\PaginatedCollectionResponse;

class SearchOperationBuilder extends OperationBuilder
{
    /**
     * @return Operation
     * @throws BindingResolutionException
     */
    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Search for {$this->resolveResourceName(true)}";

        return $operation;
    }

    //TODO: request

    /**
     * @return array
     * @throws BindingResolutionException
     */
    protected function responses(): array
    {
        return [
            new PaginatedCollectionResponse($this->resolveResourceComponentBaseName()),
            new UnauthenticatedResponse(),
            new UnauthorizedResponse(),
        ];
    }
}
