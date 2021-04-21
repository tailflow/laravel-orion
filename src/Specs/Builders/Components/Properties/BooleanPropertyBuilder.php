<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Properties;

use Doctrine\DBAL\Schema\Column;
use Orion\Specs\Builders\Components\PropertyBuilder;
use Orion\ValueObjects\Specs\Schema\Properties\BooleanSchemaProperty;
use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class BooleanPropertyBuilder extends PropertyBuilder
{
    public function build(Column $column, SchemaProperty $baseProperty): BooleanSchemaProperty
    {
        $property = BooleanSchemaProperty::fromBase($baseProperty);

        return $property;
    }
}
