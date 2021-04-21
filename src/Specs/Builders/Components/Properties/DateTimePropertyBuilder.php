<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Properties;

use Doctrine\DBAL\Schema\Column;
use Orion\Specs\Builders\Components\PropertyBuilder;
use Orion\ValueObjects\Specs\Schema\Properties\DateTimeSchemaProperty;
use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class DateTimePropertyBuilder extends PropertyBuilder
{
    public function build(Column $column, SchemaProperty $baseProperty): DateTimeSchemaProperty
    {
        $property = DateTimeSchemaProperty::fromBase($baseProperty);

        return $property;
    }
}
