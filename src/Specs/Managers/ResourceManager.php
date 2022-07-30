<?php

declare(strict_types=1);

namespace Orion\Specs\Managers;

use DateTimeInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Orion\Http\Resources\Resource;
use Orion\ValueObjects\Specs\Schema\Properties\AnySchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\ArraySchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\BooleanSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\DateTimeSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\IntegerSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\NumberSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\StringSchemaProperty;

class ResourceManager
{
    public function getResourceProperties(Resource $resourceResource): array
    {
        return $resourceResource->resolve();
    }

    /**
     * @param string $property
     * @param $value
     * @return string
     */
    public function resolveResourcePropertyClass(string $property, $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeSchemaProperty::class;
        }

        if (is_int($value)) {
            return IntegerSchemaProperty::class;
        }

        if (is_bool($value)) {
            return BooleanSchemaProperty::class;
        }

        if (is_float($value)) {
            return NumberSchemaProperty::class;
        }

        if (is_string($value)) {
            return StringSchemaProperty::class;
        }

        if (is_array($value) || is_a($value, ResourceCollection::class) || is_a($value, Collection::class)) {
            return ArraySchemaProperty::class;
        }

        if (is_object($value) || is_a($value, JsonResource::class)) {
            return ObjectSchemaProperty::class;
        }

        return AnySchemaProperty::class;
    }
}
