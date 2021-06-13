<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Request;
use Orion\ValueObjects\Specs\Requests\StoreRequest;
use Orion\ValueObjects\Specs\Responses\Error\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthorizedResponse;
use Orion\ValueObjects\Specs\Responses\Error\ValidationErrorResponse;
use Orion\ValueObjects\Specs\Responses\Success\EntityResponse;

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
