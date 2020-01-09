<?php

namespace Orion\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Orion\Http\Requests\Request;
use Orion\Http\Rules\WhitelistedField;

trait BuildsQuery
{
    /**
     * Get Eloquent query builder for the model and apply filters, searching and sorting.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildQuery(Request $request)
    {
        /**
         * @var Builder $query
         */
        $query = static::$model::query();
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
     * @param Request $request
     * @return Builder
     */
    protected function buildMethodQuery(Request $request)
    {
        $method = debug_backtrace()[1]['function'];
        $customQueryMethod = 'build'.ucfirst($method).'Query';

        if (method_exists($this, $customQueryMethod)) {
            return $this->{$customQueryMethod}($request);
        }
        return $this->buildQuery($request);
    }

    /**
     * Build the list of relations allowed to be included together with a resource based on the "include" query parameter.
     *
     * @param Request $request
     * @return array
     */
    protected function relationsFromIncludes(Request $request)
    {
        $requestedIncludesStr = $request->get('include', '');
        $requestedIncludes = explode(',', $requestedIncludesStr);

        $allowedIncludes = array_unique(array_merge($this->includes(), $this->alwaysIncludes()));

        $validatedIncludes = array_filter($requestedIncludes, function ($include) use ($allowedIncludes) {
            return in_array($include, $allowedIncludes, true);
        });

        return array_unique(array_merge($validatedIncludes, $this->alwaysIncludes()));
    }

    /**
     * Determine the pagination limit based on the "limit" query parameter or the default, specified by developer.
     *
     * @param Request $request
     * @return int
     */
    protected function resolvePaginationLimit(Request $request)
    {
        $limit = (int) $request->get('limit', $this->limit());
        return $limit > 0 ? $limit : $this->limit();
    }

    /**
     * Apply sorting to the given query builder based on the "sort" query parameter.
     *
     * @param Request $request
     * @param Builder|Relation $query
     */
    protected function applySortingToQuery(Request $request, $query)
    {
        if (!$requestedSortableDescriptors = $request->get('sort')) {
            return;
        }

        $this->validate($request, [
            'sort' => ['sometimes', 'array'],
            'sort.*.field' => ['required_with:sort', 'regex:/^[\w.]+$/', new WhitelistedField($this->sortableBy())],
            'sort.*.direction' => ['sometimes', 'in:asc,desc']
        ]);

        $sortableDescriptors = $request->get('sort');

        foreach ($sortableDescriptors as $sortable) {
            $sortableField = $sortable['field'];
            $direction = Arr::get($sortable, 'direction', 'asc');

            if (strpos($sortableField, '.') !== false) {
                $relation = $this->relationFromParamConstraint($sortableField);
                $relationField = $this->relationFieldFromParamConstraint($sortableField);

                $model = $this->getResourceModel();
                /**
                 * @var BelongsTo|HasOne|HasOneThrough $relationInstance
                 */
                $relationInstance = (new $model)->{$relation}();
                $relationTable = $relationInstance->getModel()->getTable();

                $relationForeignKey = $this->relationForeignKeyFromRelationInstance($relationInstance);
                $relationLocalKey = $this->relationLocalKeyFromRelationInstance($relationInstance);

                $query->leftJoin($relationTable, $relationForeignKey, '=', $relationLocalKey)
                    ->orderBy("$relationTable.$relationField", $direction)
                    ->select((new $model)->getTable().'.*');
            } else {
                $query->orderBy($sortableField, $direction);
            }
        }
    }

    /**
     * Apply scopes to the given query builder based on the query parameters.
     *
     * @param Request $request
     * @param Builder|Relation $query
     */
    protected function applyScopesToQuery(Request $request, $query)
    {
        if (!$requestedScopeDescriptors = $request->get('scopes')) {
            return;
        }

        $this->validate($request, [
            'scopes' => ['sometimes', 'array'],
            'scopes.*.name' => ['required_with:scopes', 'in:'.implode(',', $this->exposedScopes())],
            'scopes.*.parameters' => ['sometimes', 'array']
        ]);

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
    protected function applyFiltersToQuery(Request $request, $query)
    {
        if (!$requestedFilterDescriptors = $request->get('filters')) {
            return;
        }

        $this->validate($request, [
            'filters' => ['sometimes', 'array'],
            'filters.*.type' => ['sometimes', 'in:and,or'],
            'filters.*.field' => ['required_with:filters', 'regex:/^[\w.]+$/', new WhitelistedField($this->filterableBy())],
            'filters.*.operator' => ['required_with:filters', 'in:<,<=,>,>=,=,!=,like,not like,in,not in'],
            'filters.*.value' => ['required_with:filters', 'nullable']
        ]);

        $filterableDescriptors = $request->get('filters');

        foreach ($filterableDescriptors as $filterable) {
            $or = Arr::get($filterable, 'type', 'and') === 'or';

            if (strpos($filterable['field'], '.') !== false) {
                $relation = $this->relationFromParamConstraint($filterable['field']);
                $relationField = $this->relationFieldFromParamConstraint($filterable['field']);

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
    protected function buildFilterQueryWhereClause($field, $filterable, $query, $or = false)
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
    protected function applySearchingToQuery(Request $request, $query)
    {
        if (!$requestedSearchDescriptor = $request->get('search')) {
            return;
        }

        $this->validate($request, [
            'search' => ['sometimes', 'array'],
            'search.value' => ['string', 'nullable']
        ]);

        $searchables = $this->searchableBy();
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
                    $relation = $this->relationFromParamConstraint($searchable);
                    $relationField = $this->relationFieldFromParamConstraint($searchable);

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
     * Apply "soft deletes" query to the given query builder based on either "with_trashed" or "only_trashed" query parameters.
     *
     * @param Request $request
     * @param Builder|Relation $query
     */
    protected function applySoftDeletesToQuery(Request $request, $query)
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

    /**
     * Resolves relation name from the given param constraint.
     *
     * @param string $paramConstraint
     * @return string
     */
    protected function relationFromParamConstraint(string $paramConstraint)
    {
        $paramConstraintParts = explode('.', $paramConstraint);
        if (count($paramConstraintParts) === 2) {
            return Arr::first($paramConstraintParts);
        }

        return implode('.', array_slice($paramConstraintParts, -1));
    }

    /**
     * Resolves relation field from the given param constraint.
     *
     * @param string $paramConstraint
     * @return string
     */
    protected function relationFieldFromParamConstraint(string $paramConstraint)
    {
        return Arr::last(explode('.', $paramConstraint));
    }

    /**
     * Resolves relation foreign key from the given relation instance.
     *
     * @param BelongsTo|HasOne|HasOneThrough $relationInstance
     * @return string
     */
    protected function relationForeignKeyFromRelationInstance($relationInstance)
    {
        $laravelVersion = (float) app()->version();

        return $laravelVersion > 5.7 || get_class($relationInstance) === HasOne::class ? $relationInstance->getQualifiedForeignKeyName() : $relationInstance->getQualifiedForeignKey();
    }

    /**
     * Resolves relation local key from the given relation instance.
     *
     * @param BelongsTo|HasOne|HasOneThrough $relationInstance
     * @return string
     */
    protected function relationLocalKeyFromRelationInstance($relationInstance)
    {
        switch (get_class($relationInstance)) {
            case HasOne::class:
                return $relationInstance->getParent()->getTable().'.'.$relationInstance->getLocalKeyName();
                break;
            case BelongsTo::class:
                return $relationInstance->getQualifiedOwnerKeyName();
                break;
            default:
                return $relationInstance->getQualifiedLocalKeyName();
                break;
        }
    }

    /**
     * Determine whether the resource model uses soft deletes.
     *
     * @return bool
     */
    protected function softDeletes()
    {
        $modelClass = $this->getResourceModel();
        return method_exists(new $modelClass, 'trashed');
    }
}
