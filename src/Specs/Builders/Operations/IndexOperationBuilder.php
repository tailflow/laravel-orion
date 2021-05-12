<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Responses\Error\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthorizedResponse;
use Orion\ValueObjects\Specs\Responses\Success\PaginatedCollectionResponse;

class IndexOperationBuilder extends OperationBuilder
{
    /**
     * @return Operation
     * @throws BindingResolutionException
     */
    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Get a list of {$this->resolveResourceName(true)}";

        return $operation;
    }

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
