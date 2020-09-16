<?php

namespace Orion\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait BuildsResponses
{
    /**
     * @param Model $entity
     * @return JsonResource
     */
    public function entityResponse(Model $entity): JsonResource
    {
        $resource = $this->getResource();

        return new $resource($entity);
    }

    /**
     * @param LengthAwarePaginator $entities
     * @return ResourceCollection
     */
    public function collectionResponse(LengthAwarePaginator $entities): ResourceCollection
    {
        if ($collectionResource = $this->getCollectionResource()) {
            return new $collectionResource($entities);
        }

        $resource = $this->getResource();

        return $resource::collection($entities);
    }
}
