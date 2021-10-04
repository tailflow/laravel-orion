<?php

declare(strict_types=1);

namespace Orion\Operations\Standard;

use Orion\Http\Transformers\EntityTransformer;
use Orion\Operations\MutatingOperation;

class UpdateOperation extends MutatingOperation
{
    protected EntityTransformer $transformer;

    public function __construct(
        EntityTransformer $entityTransformer
    ) {
        parent::__construct();

        $this->transformer = $entityTransformer;
    }

    public function refresh($payload)
    {
        $payload->entity = $payload->entity->fresh($payload->requestedRelations);

        return $payload;
    }

    public function perform($payload)
    {
        $payload->entity->save();

        return $payload;
    }

    public function transform($payload)
    {
        return $this->transformer->transform($payload->entity, $this->resourceClass);
    }
}
