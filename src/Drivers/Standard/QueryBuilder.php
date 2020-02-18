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
     * @var string $resourceModelClass
     */
    private $resourceModelClass;

    /**
     * @var \Orion\Contracts\ParamsValidator $paramsValidator
     */
    private $paramsValidator;

    /**
     * @var \Orion\Contracts\RelationsResolver $relationsResolver
     */
    private $relationsResolver;

    /**
     * @var \Orion\Contracts\SearchBuilder $searchBuilder
     */
    private $searchBuilder;

    /**
     * @inheritDoc
     */
    public function __construct(
        string $resourceModelClass,
        \Orion\Contracts\ParamsValidator $paramsValidator,
        \Orion\Contracts\RelationsResolver $relationsResolver,
        \Orion\Contracts\SearchBuilder $searchBuilder
    ) {
        $this->resourceModelClass = $resourceModelClass;
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
                $this->applyScopesToQuery($query, $request);
                $this->applyFiltersToQuery($query, $request);
                $this->applySearchingToQuery($query, $request);
                $this->applySortingToQuery($query, $request);
            }
            $this->applySoftDeletesToQuery($query, $request);
        }

        return $query;
    }

    /**
     * Apply scopes to the given query builder based on the query parameters.
     *
     * @param Builder $query
     * @param Request $request
     */
    public function applyScopesToQuery(Builder $query, Request $request): void
    {
        $this->paramsValidator->validateScopes($request);
        $scopeDescriptors = $request->get('scopes', []);

        foreach ($scopeDescriptors as $scopeDescriptor) {
            $query->{$scopeDescriptor['name']}(...Arr::get($scopeDescriptor, 'parameters', []));
        }
    }

    /**
     * Apply filters to the given query builder based on the query parameters.
     *
     * @param Builder $query
     * @param Request $request
     */
    public function applyFiltersToQuery(Builder $query, Request $request): void
    {
        $this->paramsValidator->validateFilters($request);
        $filterDescriptors = $request->get('filters', []);

        foreach ($filterDescriptors as $filterDescriptor) {
            $or = Arr::get($filterDescriptor, 'type', 'and') === 'or';

            if (strpos($filterDescriptor['field'], '.') !== false) {
                $relation = $this->relationsResolver->relationFromParamConstraint($filterDescriptor['field']);
                $relationField = $this->relationsResolver->relationFieldFromParamConstraint($filterDescriptor['field']);

                $query->{$or ? 'orWhereHas' : 'whereHas'}($relation, function ($relationQuery) use ($relationField, $filterDescriptor) {
                    /**
                     * @var \Illuminate\Database\Query\Builder $relationQuery
                     */
                    $this->buildFilterQueryWhereClause($relationField, $filterDescriptor, $relationQuery);
                });
            } else {
                $this->buildFilterQueryWhereClause($filterDescriptor['field'], $filterDescriptor, $query, $or);
            }
        }
    }

    /**
     * Builds filter's query where clause based on the given filterable.
     *
     * @param string $field
     * @param array $filterDescriptor
     * @param Builder|\Illuminate\Database\Query\Builder $query
     * @param bool $or
     * @return Builder
     */
    protected function buildFilterQueryWhereClause(string $field, array $filterDescriptor, $query, bool $or = false): Builder
    {
        if (!is_array($filterDescriptor['value'])) {
            $query->{$or ? 'orWhere' : 'where'}($field, $filterDescriptor['operator'], $filterDescriptor['value']);
        } else {
            $query->{$or ? 'orWhereIn' : 'whereIn'}($field, $filterDescriptor['value'], 'and', $filterDescriptor['operator'] === 'not in');
        }

        return $query;
    }

    /**
     * Apply search query to the given query builder based on the "q" query parameter.
     *
     * @param Builder $query
     * @param Request $request
     */
    public function applySearchingToQuery(Builder $query, Request $request): void
    {
        if (!$requestedSearchDescriptor = $request->get('search')) {
            return;
        }

        $this->paramsValidator->validateSearch($request);

        $searchables = $this->searchBuilder->searchableBy();

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
     * @param Builder $query
     * @param Request $request
     */
    public function applySortingToQuery(Builder $query, Request $request): void
    {
        $this->paramsValidator->validateSort($request);
        $sortableDescriptors = $request->get('sort', []);

        foreach ($sortableDescriptors as $sortable) {
            $sortableField = $sortable['field'];
            $direction = Arr::get($sortable, 'direction', 'asc');

            if (strpos($sortableField, '.') !== false) {
                $relation = $this->relationsResolver->relationFromParamConstraint($sortableField);
                $relationField = $this->relationsResolver->relationFieldFromParamConstraint($sortableField);

                /**
                 * @var BelongsTo|HasOne|HasOneThrough $relationInstance
                 */
                $relationInstance = (new $this->resourceModelClass)->{$relation}();
                $relationTable = $relationInstance->getModel()->getTable();

                $relationForeignKey = $this->relationsResolver->relationForeignKeyFromRelationInstance($relationInstance);
                $relationLocalKey = $this->relationsResolver->relationLocalKeyFromRelationInstance($relationInstance);

                $query->leftJoin($relationTable, $relationForeignKey, '=', $relationLocalKey)
                    ->orderBy("$relationTable.$relationField", $direction)
                    ->select((new $this->resourceModelClass)->getTable().'.*');
            } else {
                $query->orderBy($sortableField, $direction);
            }
        }
    }

    /**
     * Apply "soft deletes" query to the given query builder based on either "with_trashed" or "only_trashed" query parameters.
     *
     * @param Builder $query
     * @param Request $request
     * @return bool
     */
    public function applySoftDeletesToQuery(Builder $query, Request $request): bool
    {
        if (!$query->getMacro('withTrashed')) {
            return false;
        }

        if ($request->has('with_trashed')) {
            $query->withTrashed();
        } elseif ($request->has('only_trashed')) {
            $query->onlyTrashed();
        }

        return true;
    }
}
