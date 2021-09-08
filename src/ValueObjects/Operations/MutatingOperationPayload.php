<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Operations;

use Illuminate\Database\Eloquent\Model;
use Orion\Http\Requests\Request;

abstract class MutatingOperationPayload extends OperationPayload
{
    public ?array $attributes;

    public Model $entity;

    public function __construct(Model $entity, Request $request, array $requestedRelations = [])
    {
        parent::__construct($request, $requestedRelations);

        $this->entity = $entity;
    }
}
