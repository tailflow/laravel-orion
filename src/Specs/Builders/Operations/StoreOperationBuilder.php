<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Request;
use Orion\ValueObjects\Specs\Requests\StoreRequest;
use Orion\ValueObjects\Specs\Responses\EntityResponse;
use Orion\ValueObjects\Specs\Responses\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\UnauthorizedResponse;
use Orion\ValueObjects\Specs\Responses\ValidationErrorResponse;

class StoreOperationBuilder extends OperationBuilder
{
    /**
     * @return Operation
     * @throws BindingResolutionException
     */
    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Create {$this->resolveResourceName()}";

        return $operation;
    }

    /**
     * @return Request|null
     * @throws BindingResolutionException
     */
    protected function request(): ?Request
    {
        return new StoreRequest($this->resolveResourceComponentBaseName());
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    protected function responses(): array
    {
        return [
            new EntityResponse($this->resolveResourceComponentBaseName(), 201),
            new UnauthenticatedResponse(),
            new UnauthorizedResponse(),
            new ValidationErrorResponse(),
        ];
    }
}
