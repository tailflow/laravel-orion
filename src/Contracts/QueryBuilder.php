<?php

namespace Orion\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Orion\Http\Requests\Request;

interface QueryBuilder
{
    /**
     * QueryBuilder constructor.
     *
     * @param string $modelClass
     * @param ParamsValidator $paramsValidator
     * @param RelationsResolver $relationsResolver
     * @param SearchBuilder $searchBuilder
     */
    public function __construct(string $modelClass, ParamsValidator $paramsValidator, RelationsResolver $relationsResolver, SearchBuilder $searchBuilder);

    public function buildQuery(Builder $query, Request $request): Builder;

    public function buildMethodQuery(Builder $query, Request $request): Builder;

    public function applyScopesToQuery(Request $request, Builder $query): void;

    public function applyFiltersToQuery(Request $request, Builder $query): void;

    public function applySearchingToQuery(Request $request, Builder $query): void;

    public function applySortingToQuery(Request $request, Builder $query): void;

    public function applySoftDeletesToQuery(Request $request, Builder $query): void;
}
