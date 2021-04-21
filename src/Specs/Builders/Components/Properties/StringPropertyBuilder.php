<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Properties;

use Doctrine\DBAL\Schema\Column;
use Orion\Specs\Builders\Components\PropertyBuilder;
use Orion\ValueObjects\Specs\Schema\Properties\StringSchemaProperty;
use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class StringPropertyBuilder extends PropertyBuilder
{
    public function build(Column $column, SchemaProperty $baseProperty): StringSchemaProperty
    {
        $property = StringSchemaProperty::fromBase($baseProperty);

        return $property;
    }
}
