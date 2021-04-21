<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Properties;

use Doctrine\DBAL\Schema\Column;
use Orion\Specs\Builders\Components\PropertyBuilder;
use Orion\ValueObjects\Specs\Schema\Properties\IntegerSchemaProperty;
use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class IntegerPropertyBuilder extends PropertyBuilder
{
    public function build(Column $column, SchemaProperty $baseProperty): IntegerSchemaProperty
    {
        $property = IntegerSchemaProperty::fromBase($baseProperty);

        return $property;
    }
}
