<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema;

use Illuminate\Contracts\Support\Arrayable;

class SchemaProperty implements Arrayable
{
    /** @var string */
    public $name;
    /** @var string */
    public $type;

    /**
     * @param SchemaProperty $baseSchemaProperty
     *
     * @return static
     */
    public static function fromBase(SchemaProperty $baseSchemaProperty)
    {
        $schemaProperty = new static();
        $schemaProperty->name = $baseSchemaProperty->name;

        return $schemaProperty;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
        ];
    }
}
