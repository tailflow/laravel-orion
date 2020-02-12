<?php

namespace Orion\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait BuildsResponses
{
    /**
     * @param Model $entity
     * @return Resource
     */
    public function entityResponse(Model $entity): Resource
    {
        $resource = $this->getResource();

        return new $resource($entity);
    }

    /**
     * @param $entities
     * @return ResourceCollection
     */
    public function collectionResponse($entities): ResourceCollection
    {
        if ($collectionResource = $this->getCollectionResource()) {
            return new $collectionResource($entities);
        }

        $resource = $this->getResource();

        return $resource::collection($entities);
    }
}
