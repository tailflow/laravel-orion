<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components;

use Doctrine\DBAL\Schema\Column;
use Orion\ValueObjects\Specs\Schema\SchemaProperty;

abstract class PropertyBuilder
{
    /**
     * @param Column $column
     * @param SchemaProperty $baseProperty
     *
     * @return SchemaProperty
     */
    abstract public function build(Column $column, SchemaProperty $baseProperty);

    /**
     * @param Column $column
     *
     * @return SchemaProperty
     */
    public function makeBaseProperty(Column $column)
    {
        $property = new SchemaProperty();
        $property->name = $column->getName();
        $property->type = $column->getType();

        return $property;
    }
}
