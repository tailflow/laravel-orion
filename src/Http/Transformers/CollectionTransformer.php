<?php

declare(strict_types=1);

namespace Orion\Http\Transformers;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class CollectionTransformer
{
    public function transform(
        Collection $entities,
        string $resourceClass,
        ?string $collectionResourceClass
    ): ResourceCollection {
        if ($collectionResourceClass) {
            return new $collectionResourceClass($entities);
        }

        return $resourceClass::collection($entities);
    }
}
