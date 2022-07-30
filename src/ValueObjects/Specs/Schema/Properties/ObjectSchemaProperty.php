<?php

declare(strict_types=1);

namespace App\OpenApi\ValueObjects\Specs\Schema\Properties;

use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class ObjectSchemaProperty extends SchemaProperty
{
    public $type = 'object';
}
