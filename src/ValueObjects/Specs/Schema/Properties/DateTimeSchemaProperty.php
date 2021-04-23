<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema\Properties;

use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class DateTimeSchemaProperty extends SchemaProperty
{
    public $type = 'string';

    public function toArray(): array
    {
        return array_merge(
            parent::toArray(),
            [
                'format' => 'date-time',
            ],
        );
    }
}
