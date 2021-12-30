<?php

namespace Orion\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Orion\Http\Resources\Resource;

trait BuildsResponses
{
    protected $meta = [];

    /**
     * @param Model $entity
     * @return JsonResource
     */
    public function entityResponse(Model $entity): JsonResource
    {
        $resourceClass = $this->getResource();

        /** @var Resource $resource */
        $resource = new $resourceClass($entity);

        return $this->addMetaToResource($resource);
    }

    /**
     * @param LengthAwarePaginator|Collection $entities
     * @return ResourceCollection
     */
    public function collectionResponse($entities): ResourceCollection
    {
        if ($collectionResourceClass = $this->getCollectionResource()) {
            $collectionResource = new $collectionResourceClass($entities);
        } else {
            $resource = $this->getResource();

            $collectionResource = $resource::collection($entities);
        }


        return $this->addMetaToResource($collectionResource);
    }

    public function withMeta(string $key, $value): self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * @param JsonResource|ResourceCollection $resource
     * @return JsonResource|ResourceCollection
     */
    protected function addMetaToResource($resource)
    {
        if (count($this->meta)) {
            $resource->additional(
                [
                    'meta' => $this->meta,
                ]
            );
        }

        return $resource;
    }
}
