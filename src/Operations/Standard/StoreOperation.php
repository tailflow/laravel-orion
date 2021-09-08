<?php

declare(strict_types=1);

namespace Orion\Operations\Standard;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Resources\Json\JsonResource;
use Orion\Contracts\RelationsResolver;
use Orion\Http\Transformers\EntityTransformer;
use Orion\Operations\MutatingOperation;
use Orion\ValueObjects\Operations\Standard\StoreOperationPayload;

class StoreOperation extends MutatingOperation
{
    protected EntityTransformer $transformer;

    public function __construct(
        EntityTransformer $entityTransformer,
        RelationsResolver $relationsResolver,
        Container $container = null
    ) {
        parent::__construct($relationsResolver, $container);

        $this->transformer = $entityTransformer;
    }

    /**
     * @param StoreOperationPayload $payload
     * @return StoreOperationPayload
     */
    public function refresh($payload): StoreOperationPayload
    {
        $payload->entity = $payload->entity->fresh($payload->requestedRelations);
        $payload->entity->wasRecentlyCreated = true;

        return $payload;
    }

    /**
     * @param StoreOperationPayload $payload
     * @return StoreOperationPayload
     */
    public function perform($payload): StoreOperationPayload
    {
        $payload->entity->save();

        return $payload;
    }

    /**
     * @param StoreOperationPayload $payload
     * @return StoreOperationPayload
     */
    public function guard($payload): StoreOperationPayload
    {
        $payload->entity = $this->relationsResolver->guardRelations($payload->entity, $payload->requestedRelations);

        return $payload;
    }

    /**
     * @param StoreOperationPayload $payload
     * @return JsonResource
     */
    public function transform($payload): JsonResource
    {
        return $this->transformer->transform($payload->entity, $this->resourceClass);
    }
}
