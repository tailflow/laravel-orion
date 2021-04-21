<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Properties;

use Doctrine\DBAL\Schema\Column;
use Orion\Specs\Builders\Components\PropertyBuilder;
use Orion\ValueObjects\Specs\Schema\Properties\NumberSchemaProperty;
use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class NumberPropertyBuilder extends PropertyBuilder
{
    public function build(Column $column, SchemaProperty $baseProperty): NumberSchemaProperty
    {
        $property = NumberSchemaProperty::fromBase($baseProperty);

        return $property;
    }
}
