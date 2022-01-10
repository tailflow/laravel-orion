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
     * @param SearchEngine $searchEngine
     * @param bool $intermediateMode
     */
    public function __construct(string $resourceModelClass, ParamsValidator $paramsValidator, RelationsResolver $relationsResolver, SearchEngine $searchEngine, bool $intermediateMode = false);

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
    public function applyScopesToQuery($query, Request $request);

    /**
     * @param Builder|Relation $query
     * @param Request $request
     * @param array $filterDescriptors
     */
    public function applyFiltersToQuery($query, Request $request, array $filterDescriptors = []);

    /**
     * @param Builder|Relation $query
     * @param Request $request
     */
    public function applySearchingToQuery($query, Request $request);

    /**
     * @param Builder|Relation $query
     * @param Request $request
     */
    public function applySortingToQuery($query, Request $request);

    /**
     * @param Builder|Relation $query
     * @param Request $request
     */
    public function applySoftDeletesToQuery($query, Request $request);
}
