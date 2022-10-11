<?php

namespace Orion\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
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
     * @param bool $intermediateMode
     */
    public function __construct(string $resourceModelClass, ParamsValidator $paramsValidator, RelationsResolver $relationsResolver, SearchBuilder $searchBuilder, bool $intermediateMode = false);

    /**
     * @param Builder|Relation $query
     * @param Request $request
     * @return Builder|Relation
     */
    public function buildQuery($query, Request $request);

    /**
     * @param Builder|Relation $query
     * @param Request $request
     */
    public function applyScopesToQuery($query, Request $request): void;

    /**
     * @param Builder|Relation $query
     * @param Request $request
     * @param array $filterDescriptors
     */
    public function applyFiltersToQuery($query, Request $request, array $filterDescriptors = []): void;

    /**
     * @param Builder|Relation $query
     * @param Request $request
     */
    public function applySearchingToQuery($query, Request $request): void;

    /**
     * @param Builder|Relation $query
     * @param Request $request
     */
    public function applySortingToQuery($query, Request $request): void;

    /**
     * @param Builder|Relation $query
     * @param Request $request
     * @return bool
     */
    public function applySoftDeletesToQuery($query, Request $request): bool;

    /**
     * @param Builder|Relation $query
     * @param Request $request
     * @param array $aggregateDescriptors
     * @return void
     */
    public function applyAggregatesToQuery($query, Request $request, array $aggregateDescriptors = []): void;

    /**
     * @param Builder|Relation $query
     * @param Request $request
     * @param array $includeDescriptors
     * @return void
     */
    public function applyIncludesToQuery($query, Request $request, array $includeDescriptors = []): void;
}
