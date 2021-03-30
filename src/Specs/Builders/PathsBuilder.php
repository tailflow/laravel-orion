<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

class PathsBuilder
{
    /** @var OperationsBuilder  */
    protected $operationsBuilder;

    public function __construct(OperationsBuilder $operationsBuilder)
    {
        $this->operationsBuilder = $operationsBuilder;
    }

    public function build(): array {

    }
}