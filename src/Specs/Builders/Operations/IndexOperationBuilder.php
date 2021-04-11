<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;

class IndexOperationBuilder extends OperationBuilder
{
    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = 'Get a list of resources';

        return $operation;
    }
}
