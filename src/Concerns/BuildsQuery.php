<?php

namespace Orion\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Orion\Http\Requests\Request;

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
        [, $action] = explode('@', $request->route()->getActionName());

        if (in_array($action, ['index', 'show'])) {
            if ($action === 'index') {
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
            'sort.*' => ['required_with:sort', 'regex:/^[\w.]+\|?(asc|desc)*$/']
        ]);

        $allowedSortables = $this->sortableBy();

        $validatedSortableDescriptors = array_filter($requestedSortableDescriptors, function ($sortableDescriptor) use ($allowedSortables) {
            $sortableDescriptorParams = array_filter(explode('|', $sortableDescriptor));
            if (count($sortableDescriptorParams) !== 2) {
                return false;
            }

            [$sortable, $direction] = $sortableDescriptorParams;

            return in_array($direction, ['asc', 'desc'], true) && $this->validParamConstraint($sortable, $allowedSortables);
        });

        foreach ($validatedSortableDescriptors as $sortableDescriptor) {
            [$sortable, $direction] = explode('|', $sortableDescriptor);

            if (strpos($sortable, '.') !== false) {
                $relation = $this->relationFromParamConstraint($sortable);
                $relationField = $this->relationFieldFromParamConstraint($sortable);

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
                $query->orderBy($sortable, $direction);
            }
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
        if (!$requestedFilterDescriptors = $request->get('filter')) {
            return;
        }

        $this->validate($request, [
            'filter' => ['sometimes', 'array'],
            'filter.*.field' => ['required_with:filter', 'regex:/^[\w.]+$/'],
            'filter.*.operator' => ['required_with:filter', 'in:<,<=,>,>=,=,!=,like,not like,in,not in'],
            'filter.*.value' => ['required_with:filter', 'nullable']
        ]);

        $allowedFilterables = $this->filterableBy();

        $validatedFilterables = array_filter($requestedFilterDescriptors, function ($filterable) use ($allowedFilterables) {
            return $this->validParamConstraint($filterable['field'], $allowedFilterables);
        });

        foreach ($validatedFilterables as $filterable) {
            if (strpos($filterable['field'], '.') !== false) {
                $relation = $this->relationFromParamConstraint($filterable['field']);
                $relationField = $this->relationFieldFromParamConstraint($filterable['field']);

                $query->whereHas($relation, function ($relationQuery) use ($relationField, $filterable) {
                    /**
                     * @var \Illuminate\Database\Query\Builder $relationQuery
                     */
                    $this->buildFilterWhereClause($relationField, $filterable, $relationQuery);
                });
            } else {
                $this->buildFilterWhereClause($filterable['field'], $filterable, $query);
            }
        }
    }

    /**
     * Builds filter's query where clause based on the given filterable.
     *
     * @param string $field
     * @param array $filterable
     * @param Builder|\Illuminate\Database\Query\Builder $query
     * @return Builder|Relation
     */
    protected function buildFilterWhereClause($field, $filterable, $query)
    {
        if (!is_array($filterable['value'])) {
            $query->where($field, $filterable['operator'], $filterable['value']);
        } else {
            $query->whereIn($field, $filterable['value'], 'and', $filterable['operator'] === 'not in');
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
            'search.q' => ['string', 'nullable']
        ]);

        $searchables = $this->searchableBy();
        if (!count($searchables)) {
            return;
        }


        $query->where(function ($whereQuery) use ($searchables, $requestedSearchDescriptor) {
            $requestedSearchString = $requestedSearchDescriptor['q'];
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
     * Validate the param constraint against allowed param constraints.
     *
     * @param string $paramConstraint
     * @param array $allowedParamConstraints
     * @return bool
     */
    protected function validParamConstraint(string $paramConstraint, array $allowedParamConstraints)
    {
        if (in_array('*', $allowedParamConstraints, true)) {
            return true;
        }
        if (in_array($paramConstraint, $allowedParamConstraints, true)) {
            return true;
        }

        if (strpos($paramConstraint, '.') === false) {
            return false;
        }

        $allowedNestedParamConstraints = array_filter($allowedParamConstraints, function ($allowedParamConstraint) {
            return strpos($allowedParamConstraint, '.*') !== false;
        });

        $paramConstraintNestingLevel = substr_count($paramConstraint, '.');

        foreach ($allowedNestedParamConstraints as $allowedNestedParamConstraint) {
            $allowedNestedParamConstraintNestingLevel = substr_count($allowedNestedParamConstraint, '.');
            $allowedNestedParamConstraintReduced = explode('.*', $allowedNestedParamConstraint)[0];

            for ($i = 0; $i < $allowedNestedParamConstraintNestingLevel; $i++) {
                $allowedNestedParamConstraintReduced = implode('.', array_slice(explode('.', $allowedNestedParamConstraintReduced), -$i));

                $paramConstraintReduced = $paramConstraint;
                for ($k = 1; $k < $paramConstraintNestingLevel; $k++) {
                    $paramConstraintReduced = implode('.', array_slice(explode('.', $paramConstraintReduced), -$i));
                    if ($paramConstraintReduced === $allowedNestedParamConstraintReduced) {
                        return true;
                    }
                }
            }
        }

        return false;
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
