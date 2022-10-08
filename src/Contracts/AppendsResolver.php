<?php

declare(strict_types=1);

namespace Orion\Contracts;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Orion\Http\Requests\Request;

interface AppendsResolver
{
    public function __construct(array $alwaysAppends, array $appends);

    public function requestedAppends(Request $request): array;

    public function appendToEntity(Model $entity, Request $request): Model;

    /**
     * @param Paginator|Collection $collection
     * @param Request $request
     * @return Paginator|Collection
     */
    public function appendToCollection($collection, Request $request);
}
