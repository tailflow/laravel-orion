<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Doctrine\DBAL\Schema\Column;
use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class PropertyBuilder
{
    /**
     * @param Column $column
     * @param string|SchemaProperty $concretePropertyClass
     *
     * @return SchemaProperty
     */
    public function build(Column $column, string $concretePropertyClass): SchemaProperty
    {
        /** @var SchemaProperty $property */
        $property = new $concretePropertyClass();
        $property->name = $column->getName();
        $property->nullable = !$column->getNotnull();

        return $property;
    }

    /**
    * @param string $name
    * @param bool $nullable
    * @param string|SchemaProperty $concretePropertyClass
    *
    * @return SchemaProperty
    */
    public function buildFromResource(string $name, bool $nullable, string $concretePropertyClass): SchemaProperty
    {
        /** @var SchemaProperty $property */
        $property = new $concretePropertyClass();
        $property->name = $name;
        $property->nullable = $nullable;

        return $property;
    }
}
