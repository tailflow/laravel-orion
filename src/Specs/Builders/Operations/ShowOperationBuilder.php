<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Responses\Success\EntityResponse;

class ShowOperationBuilder extends OperationBuilder
{
    /**
     * @return Operation
     * @throws BindingResolutionException
     */
    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Get {$this->resolveResourceName()}";

        return $operation;
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    protected function responses(): array
    {
        return array_merge(
            [
                new EntityResponse($this->resolveResourceComponentBaseName()),
            ],
            parent::responses()
        );
    }
}
