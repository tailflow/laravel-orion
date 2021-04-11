<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;

class ShowOperationBuilder extends OperationBuilder
{

    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "Get {$this->resolveResourceName()}";

        return $operation;
    }
}
