<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations\Relations\OneToMany;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\RelationOperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Responses\Error\ResourceNotFoundResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthorizedResponse;
use Orion\ValueObjects\Specs\Responses\Error\ValidationErrorResponse;
use Orion\ValueObjects\Specs\Responses\Success\EntityResponse;

class DissociateOperationBuilder extends RelationOperationBuilder
{
    /**
     * @throws BindingResolutionException
     */
    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Dissociate {$this->resolveResourceName(false)} from {$this->resolveParentResourceName(false)}";

        return $operation;
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    protected function responses(): array
    {
        return [
            new EntityResponse($this->resolveResourceComponentBaseName()),
            new UnauthenticatedResponse(),
            new UnauthorizedResponse(),
            new ResourceNotFoundResponse(),
            new ValidationErrorResponse(),
        ];
    }
}
