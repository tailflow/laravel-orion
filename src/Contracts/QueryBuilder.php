<?php

namespace Orion\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Orion\Http\Requests\Request;

interface QueryBuilder
{
    /**
     * QueryBuilder constructor.
     *
     * @param string $resourceModelClass
     * @param ParamsValidator $paramsValidator
     * @param RelationsResolver $relationsResolver
     * @param SearchBuilder $searchBuilder
     */
    public function __construct(string $resourceModelClass, ParamsValidator $paramsValidator, RelationsResolver $relationsResolver, SearchBuilder $searchBuilder);

    public function buildQuery(Builder $query, Request $request): Builder;

    public function buildMethodQuery(Builder $query, Request $request): Builder;

    public function applyScopesToQuery(Builder $query, Request $request): void;

    public function applyFiltersToQuery(Builder $query, Request $request): void;

    public function applySearchingToQuery(Builder $query, Request $request): void;

    public function applySortingToQuery(Builder $query, Request $request): void;

    public function applySoftDeletesToQuery(Builder $query, Request $request): void;
}
