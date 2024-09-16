<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class PropertyBuilder
{
    /**
     * @param array $column
     * @param string $concretePropertyClass
     *
     * @return SchemaProperty
     */
    public function build(array $column, string $concretePropertyClass): SchemaProperty
    {
        /** @var SchemaProperty $property */
        $property = new $concretePropertyClass();
        $property->name = $column['name'];
        $property->nullable = $column['nullable'];

        return $property;
    }
}
