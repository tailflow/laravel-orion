<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Responses\EntityResponse;

class ShowOperationBuilder extends OperationBuilder
{

    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Get {$this->resolveResourceName()}";

        return $operation;
    }

    protected function resolveResponses(): array
    {
        return array_merge(
            [
                new EntityResponse($this->resolveResourceComponentBaseName()),
            ],
            parent::resolveResponses()
        );
    }
}
