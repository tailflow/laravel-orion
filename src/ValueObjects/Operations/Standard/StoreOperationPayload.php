<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Operations\Standard;

use Illuminate\Database\Eloquent\Model;
use Orion\Http\Requests\Request;
use Orion\ValueObjects\Operations\MutatingOperationPayload;

class StoreOperationPayload extends MutatingOperationPayload
{
    public Model $entity;

    public function __construct(Model $entity, Request $request, array $requestedRelations = [])
    {
        parent::__construct($request, $requestedRelations);

        $this->entity = $entity;
    }
}
