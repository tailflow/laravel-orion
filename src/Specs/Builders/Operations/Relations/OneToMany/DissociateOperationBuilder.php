<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations\Relations\OneToMany;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\RelationOperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Responses\EntityResponse;
use Orion\ValueObjects\Specs\Responses\ResourceNotFoundResponse;
use Orion\ValueObjects\Specs\Responses\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\UnauthorizedResponse;
use Orion\ValueObjects\Specs\Responses\ValidationErrorResponse;

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
