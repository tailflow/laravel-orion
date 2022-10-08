<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema\Properties;

use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class AnySchemaProperty extends SchemaProperty
{
    public $type = 'any';

    public function toArray(): object
    {
        return (object) [];
    }
}
