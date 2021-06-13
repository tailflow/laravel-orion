<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations\Relations\ManyToMany;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Http\Controllers\RelationController;
use Orion\Specs\Builders\RelationOperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Request;
use Orion\ValueObjects\Specs\Requests\Relations\ManyToMany\UpdatePivotRequest;
use Orion\ValueObjects\Specs\Responses\Error\ResourceNotFoundResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthorizedResponse;
use Orion\ValueObjects\Specs\Responses\Error\ValidationErrorResponse;
use Orion\ValueObjects\Specs\Responses\Success\Relation\ManyToMany\UpdatePivotResponse;

class UpdatePivotOperationBuilder extends RelationOperationBuilder
{
    /**
     * @return Operation
     */
    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Update pivot";

        return $operation;
    }

    protected function request(): ?Request
    {
        return new UpdatePivotRequest();
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    protected function responses(): array
    {
        /** @var RelationController $controller */
        $controller = app()->make($this->getResource()->controller);
        $resourceModel = app()->make($controller->resolveResourceModelClass());

        return [
            new UpdatePivotResponse($resourceModel),
            new UnauthenticatedResponse(),
            new UnauthorizedResponse(),
            new ResourceNotFoundResponse(),
            new ValidationErrorResponse(),
        ];
    }
}
