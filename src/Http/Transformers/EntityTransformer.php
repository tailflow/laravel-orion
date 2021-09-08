<?php

declare(strict_types=1);

namespace Orion\Http\Transformers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityTransformer
{
    public function transform(Model $entity, string $resourceClass): JsonResource
    {
        return new $resourceClass($entity);
    }
}
