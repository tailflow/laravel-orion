<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema\Properties;

use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class ObjectSchemaProperty extends SchemaProperty
{
    public $type = 'object';

    public function toArray(): array
    {
        $descriptor = [
            'type' => $this->type,
            'additionalProperties' => true,
        ];

        return $descriptor;
    }
}
