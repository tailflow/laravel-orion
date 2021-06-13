<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations\Relations\ManyToMany;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Http\Controllers\RelationController;
use Orion\Specs\Builders\RelationOperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Request;
use Orion\ValueObjects\Specs\Requests\Relations\ManyToMany\AttachRequest;
use Orion\ValueObjects\Specs\Responses\Error\ResourceNotFoundResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthenticatedResponse;
use Orion\ValueObjects\Specs\Responses\Error\UnauthorizedResponse;
use Orion\ValueObjects\Specs\Responses\Error\ValidationErrorResponse;
use Orion\ValueObjects\Specs\Responses\Success\Relation\ManyToMany\AttachResponse;

class AttachOperationBuilder extends RelationOperationBuilder
{
    /**
     * @return Operation
     * @throws BindingResolutionException
     */
    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Attach {$this->resolveResourceName(true)}";

        return $operation;
    }

    /**
     * @return Request|null
     */
    protected function request(): ?Request
    {
        return new AttachRequest();
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
            new AttachResponse($resourceModel),
            new UnauthenticatedResponse(),
            new UnauthorizedResponse(),
            new ResourceNotFoundResponse(),
            new ValidationErrorResponse(),
        ];
    }
}
