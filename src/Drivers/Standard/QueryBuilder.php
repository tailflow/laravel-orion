<?php

namespace Orion\Drivers\Standard;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Arr;
use Orion\Http\Requests\Request;

class QueryBuilder implements \Orion\Contracts\QueryBuilder
{
    /**
     * @var string
     */
    private $modelClass;

    /**
     * @var \Orion\Contracts\ParamsValidator
     */
    private $paramsValidator;

    /**
     * @var \Orion\Contracts\RelationsResolver
     */
    private $relationsResolver;

    /**
     * @var \Orion\Contracts\SearchBuilder
     */
    private $searchBuilder;

    /**
     * @inheritDoc
     */
    public function __construct(
        string $modelClass,
        \Orion\Contracts\ParamsValidator $paramsValidator,
        \Orion\Contracts\RelationsResolver $relationsResolver,
        \Orion\Contracts\SearchBuilder $searchBuilder
    ) {
        $this->modelClass = $modelClass;
        $this->paramsValidator = $paramsValidator;
        $this->relationsResolver = $relationsResolver;
        $this->searchBuilder = $searchBuilder;
    }

    /**
     * Get Eloquent query builder for the model and apply filters, searching and sorting.
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public function buildQuery(Builder $query, Request $request): Builder
    {
        $actionMethod = $request->route()->getActionMethod();

        if (in_array($actionMethod, ['index', 'show'])) {
            if ($actionMethod === 'index') {
                $this->applyScopesToQuery($request, $query);
                $this->applyFiltersToQuery($request, $query);
                $this->applySearchingToQuery($request, $query);
                $this->applySortingToQuery($request, $query);
            }
            $this->applySoftDeletesToQuery($request, $query);
        }

        return $query;
    }

    /**
     * Get custom query builder, if any, otherwise use default; apply filters, searching and sorting.
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public function buildMethodQuery(Builder $query, Request $request): Builder
    {
        $method = debug_backtrace()[1]['function'];
        $customQueryMethod = 'build'.ucfirst($method).'Query';

        if (method_exists($this, $customQueryMethod)) {
            return $this->{$customQueryMethod}($request);
        }
        return $this->buildQuery($query, $request);
    }

    /**
     * Apply scopes to the given query builder based on the query parameters.
     *
     * @param Request $request
     * @param Builder|Relation $query
     */
    public function applyScopesToQuery(Request $request, Builder $query): void
    {
        if (!$requestedScopeDescriptors = $request->get('scopes')) {
            return;
        }

        $this->paramsValidator->validateScopes($request);

        foreach ($requestedScopeDescriptors as $scopeDescriptor) {
            $query->{$scopeDescriptor['name']}(...Arr::get($scopeDescriptor, 'parameters', []));
        }
    }

    /**
     * Apply filters to the given query builder based on the query parameters.
     *
     * @param Request $request
     * @param Builder|Relation $query
     */
    public function applyFiltersToQuery(Request $request, Builder $query): void
    {
        if (!$filterableDescriptors = $request->get('filters')) {
            return;
        }

        $this->paramsValidator->validateFilters($request);

        foreach ($filterableDescriptors as $filterable) {
            $or = Arr::get($filterable, 'type', 'and') === 'or';

            if (strpos($filterable['field'], '.') !== false) {
                $relation = $this->relationsResolver->relationFromParamConstraint($filterable['field']);
                $relationField = $this->relationsResolver->relationFieldFromParamConstraint($filterable['field']);

                $query->{$or ? 'orWhereHas' : 'whereHas'}($relation, function ($relationQuery) use ($relationField, $filterable) {
                    /**
                     * @var \Illuminate\Database\Query\Builder $relationQuery
                     */
                    $this->buildFilterQueryWhereClause($relationField, $filterable, $relationQuery);
                });
            } else {
                $this->buildFilterQueryWhereClause($filterable['field'], $filterable, $query, $or);
            }
        }
    }

    /**
     * Builds filter's query where clause based on the given filterable.
     *
     * @param string $field
     * @param array $filterable
     * @param Builder|\Illuminate\Database\Query\Builder $query
     * @param bool $or
     * @return Builder|Relation
     */
    protected function buildFilterQueryWhereClause(string $field, array $filterable, $query, bool $or = false)
    {
        if (!is_array($filterable['value'])) {
            $query->{$or ? 'orWhere' : 'where'}($field, $filterable['operator'], $filterable['value']);
        } else {
            $query->{$or ? 'orWhereIn' : 'whereIn'}($field, $filterable['value'], 'and', $filterable['operator'] === 'not in');
        }

        return $query;
    }

    /**
     * Apply search query to the given query builder based on the "q" query parameter.
     *
     * @param Request $request
     * @param Builder|Relation $query
     */
    public function applySearchingToQuery(Request $request, Builder $query): void
    {
        if (!$requestedSearchDescriptor = $request->get('search')) {
            return;
        }

        $this->paramsValidator->validateSearch($request);

        $searchables = $this->searchBuilder->searchableBy();
        if (!count($searchables)) {
            return;
        }

        $query->where(function ($whereQuery) use ($searchables, $requestedSearchDescriptor) {
            $requestedSearchString = $requestedSearchDescriptor['value'];
            /**
             * @var Builder $whereQuery
             */
            foreach ($searchables as $searchable) {
                if (strpos($searchable, '.') !== false) {
                    $relation = $this->relationsResolver->relationFromParamConstraint($searchable);
                    $relationField = $this->relationsResolver->relationFieldFromParamConstraint($searchable);

                    $whereQuery->orWhereHas($relation, function ($relationQuery) use ($relationField, $requestedSearchString) {
                        /**
                         * @var \Illuminate\Database\Query\Builder $relationQuery
                         */
                        return $relationQuery->where($relationField, 'like', '%'.$requestedSearchString.'%');
                    });
                } else {
                    $whereQuery->orWhere($searchable, 'like', '%'.$requestedSearchString.'%');
                }
            }
        });
    }

    /**
     * Apply sorting to the given query builder based on the "sort" query parameter.
     *
     * @param Request $request
     * @param Builder|Relation $query
     */
    public function applySortingToQuery(Request $request, Builder $query): void
    {
        if (!$sortableDescriptors = $request->get('sort')) {
            return;
        }

        $this->paramsValidator->validateSort($request);

        foreach ($sortableDescriptors as $sortable) {
            $sortableField = $sortable['field'];
            $direction = Arr::get($sortable, 'direction', 'asc');

            if (strpos($sortableField, '.') !== false) {
                $relation = $this->relationsResolver->relationFromParamConstraint($sortableField);
                $relationField = $this->relationsResolver->relationFieldFromParamConstraint($sortableField);

                /**
                 * @var BelongsTo|HasOne|HasOneThrough $relationInstance
                 */
                $relationInstance = (new $this->modelClass)->{$relation}();
                $relationTable = $relationInstance->getModel()->getTable();

                $relationForeignKey = $this->relationsResolver->relationForeignKeyFromRelationInstance($relationInstance);
                $relationLocalKey = $this->relationsResolver->relationLocalKeyFromRelationInstance($relationInstance);

                $query->leftJoin($relationTable, $relationForeignKey, '=', $relationLocalKey)
                    ->orderBy("$relationTable.$relationField", $direction)
                    ->select((new $this->modelClass)->getTable().'.*');
            } else {
                $query->orderBy($sortableField, $direction);
            }
        }
    }

    /**
     * Apply "soft deletes" query to the given query builder based on either "with_trashed" or "only_trashed" query parameters.
     *
     * @param Request $request
     * @param Builder|Relation $query
     */
    public function applySoftDeletesToQuery(Request $request, Builder $query): void
    {
        if (!$query->getMacro('withTrashed')) {
            return;
        }

        if ($request->has('with_trashed')) {
            $query->withTrashed();
        } elseif ($request->has('only_trashed')) {
            $query->onlyTrashed();
        }
    }
}
