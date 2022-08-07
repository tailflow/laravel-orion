<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema\Properties;

use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class ArraySchemaProperty extends SchemaProperty
{
    public $type = 'array';

    public function toArray(): array
    {
        $descriptor = [
            'type' => $this->type,
        ];

        if ($this->nullable) {
            $descriptor['nullable'] = true;
        }

        if ($this->type === 'array') {
            $descriptor['items'] = (object) [];
        }

        return $descriptor;
    }
}
