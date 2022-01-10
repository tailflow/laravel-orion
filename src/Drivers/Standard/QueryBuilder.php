<?php

namespace Orion\Drivers\Standard;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
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
     * @var \Orion\Contracts\SearchEngine $searchEngine
     */
    private $searchEngine;

    /**
     * @var bool $intermediateMode
     */
    private $intermediateMode;

    /**
     * @inheritDoc
     */
    public function __construct(
        string $resourceModelClass,
        \Orion\Contracts\ParamsValidator $paramsValidator,
        \Orion\Contracts\RelationsResolver $relationsResolver,
        \Orion\Contracts\SearchEngine $searchEngine,
        bool $intermediateMode = false
    ) {
        $this->resourceModelClass = $resourceModelClass;
        $this->paramsValidator = $paramsValidator;
        $this->relationsResolver = $relationsResolver;
        $this->searchEngine = $searchEngine;
        $this->intermediateMode = $intermediateMode;
    }

    /**
     * Get Eloquent query builder for the model and apply filters, searching and sorting.
     *
     * @param Builder|Relation $query
     * @param Request $request
     * @return Builder|Relation
     */
    public function buildQuery($query, Request $request)
    {
        $actionMethod = $request->route()->getActionMethod();

        if (!$this->intermediateMode && in_array($actionMethod, ['index', 'search', 'show'])) {
            if ($actionMethod === 'search') {
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
     * @param Builder|Relation $query
     * @param Request $request
     */
    public function applyScopesToQuery($query, Request $request): void
    {
        $this->paramsValidator->validateScopes($request);
        $scopeDescriptors = $request->get('scopes', []);

        foreach ($scopeDescriptors as $scopeDescriptor) {
            $query = $this->searchEngine->applyScopeConstraint($query, $scopeDescriptor, $request);
        }
    }

    /**
     * Apply filters to the given query builder based on the query parameters.
     *
     * @param Builder|Relation $query
     * @param Request $request
     * @param array $filterDescriptors
     * @return Builder|Relation
     */
    public function applyFiltersToQuery($query, Request $request, array $filterDescriptors = [])
    {
        if (!$filterDescriptors) {
            $this->paramsValidator->validateFilters($request);
            $filterDescriptors = $request->get('filters', []);
        }

        foreach ($filterDescriptors as $filterDescriptor) {
            $or = Arr::get($filterDescriptor, 'type', 'and') === 'or';

            if (strpos($filterDescriptor['field'], '.') !== false) {
                $relation = $this->relationsResolver->relationFromParamConstraint($filterDescriptor['field']);
                $relationField = $this->relationsResolver->relationFieldFromParamConstraint($filterDescriptor['field']);

                if ($relation === 'pivot') {
                    $query = $this->buildPivotFilterQueryWhereClause($relationField, $filterDescriptor, $query, $request, $or);
                } else {
                    $query = $this->buildRelationFilterQueryWhereClause($relation, $relationField, $filterDescriptor, $query, $request);
                }
            } else {
                $query = $this->searchEngine->applyFieldConstraint(
                    $query,
                    $filterDescriptor['field'],
                    $filterDescriptor,
                    $or,
                    $request
                );
            }
        }

        return $query;
    }

    /**
     * Builds filter's query where clause based on the given filterable.
     *
     * @param string $relation
     * @param string $field
     * @param array $filterDescriptor
     * @param Builder|Relation $query
     * @param Request $request
     * @param bool $or
     * @return Builder|Relation
     */
    protected function buildRelationFilterQueryWhereClause(string $relation, string $field, array $filterDescriptor, $query, Request $request, bool $or = false)
    {
        if (is_array($filterDescriptor['value']) && in_array(null, $filterDescriptor['value'], true)) {
            $query = $this->searchEngine->applyRelationFieldNullConstraint(
                $query, $relation,$field, $filterDescriptor, $or, $request
            );

            $filterDescriptor['value'] = collect($filterDescriptor['value'])->filter()->values()->toArray();

            if (!count($filterDescriptor['value'])) {
                return $query;
            }
        }

        return $this->buildRelationFilterNestedQueryWhereClause($relation,$field, $filterDescriptor, $query, $request,$or);
    }

    /**
     * @param string $relation
     * @param string $field
     * @param array $filterDescriptor
     * @param Builder|Relation $query
     * @param Request $request
     * @param bool $or
     * @return Builder|Relation
     */
    protected function buildRelationFilterNestedQueryWhereClause(
        string $relation,
        string $field,
        array $filterDescriptor,
        $query,
        Request $request,
        bool $or = false
    ) {
        $treatAsDateField = $filterDescriptor['value'] !== null &&
            in_array($filterDescriptor['field'], (new $this->resourceModelClass)->getDates(), true)
            && Carbon::parse($filterDescriptor['value'])->toTimeString() === '00:00:00';

        if ($treatAsDateField) {
            return $this->searchEngine->applyRelationFieldDateConstraint(
                $query, $relation,$field, $filterDescriptor, $or, $request
            );
        }

        if (is_array($filterDescriptor['value'])) {
            return $this->searchEngine->applyRelationFieldInConstraint(
                $query, $relation,$field, $filterDescriptor, $or, $request
            );
        }

        return $this->searchEngine->applyRelationFieldConstraint(
            $query, $relation, $field, $filterDescriptor, $or, $request
        );
    }

    /**
     * Builds filter's query where clause based on the given filterable.
     *
     * @param string $field
     * @param array $filterDescriptor
     * @param Builder|Relation $query
     * @param Request $request
     * @param bool $or
     * @return Builder|Relation
     */
    protected function buildFilterQueryWhereClause(string $field, array $filterDescriptor, $query, Request $request, bool $or = false)
    {
        if (is_array($filterDescriptor['value']) && in_array(null, $filterDescriptor['value'], true)) {
            $query = $this->searchEngine->applyFieldNullConstraint(
                $query, $field, $filterDescriptor, $or, $request
            );

            $filterDescriptor['value'] = collect($filterDescriptor['value'])->filter()->values()->toArray();

            if (!count($filterDescriptor['value'])) {
                return $query;
            }
        }

        return $this->buildFilterNestedQueryWhereClause($field, $filterDescriptor, $query, $request,$or);
    }

    /**
     * @param string $field
     * @param array $filterDescriptor
     * @param Builder|Relation $query
     * @param Request $request
     * @param bool $or
     * @return Builder|Relation
     */
    protected function buildFilterNestedQueryWhereClause(
        string $field,
        array $filterDescriptor,
        $query,
        Request $request,
        bool $or = false
    ) {
        $treatAsDateField = $filterDescriptor['value'] !== null &&
            in_array($filterDescriptor['field'], (new $this->resourceModelClass)->getDates(), true)
            && Carbon::parse($filterDescriptor['value'])->toTimeString() === '00:00:00';

        if ($treatAsDateField) {
           return $this->searchEngine->applyFieldDateConstraint(
               $query, $field, $filterDescriptor, $or, $request
           );
        }

        if (is_array($filterDescriptor['value'])) {
            return $this->searchEngine->applyFieldInConstraint(
                $query, $field, $filterDescriptor, $or, $request
            );
        }

        return $this->searchEngine->applyFieldConstraint(
            $query, $field, $filterDescriptor, $or, $request
        );
    }

    /**
     * Builds filter's pivot query where clause based on the given filterable.
     *
     * @param string $field
     * @param array $filterDescriptor
     * @param BelongsToMany $query
     * @param Request $request
     * @param bool $or
     * @return BelongsToMany
     */
    protected function buildPivotFilterQueryWhereClause(
        string $field,
        array $filterDescriptor,
        $query,
        Request $request,
        bool $or = false
    ) {
        if (is_array($filterDescriptor['value']) && in_array(null, $filterDescriptor['value'], true)) {
            $query = $this->searchEngine->applyPivotFieldNullConstraint(
                $query, $field, $filterDescriptor, $or, $request
            );

            $filterDescriptor['value'] = collect($filterDescriptor['value'])->filter()->values()->toArray();

            if (!count($filterDescriptor['value'])) {
                return $query;
            }
        }

        return $this->buildPivotFilterNestedQueryWhereClause($field, $filterDescriptor, $query, $request);
    }

    /**
     * @param string $field
     * @param array $filterDescriptor
     * @param BelongsToMany $query
     * @param Request $request
     * @param bool $or
     * @return BelongsToMany
     */
    protected function buildPivotFilterNestedQueryWhereClause(
        string $field,
        array $filterDescriptor,
        $query,
        Request $request,
        bool $or = false
    ) {
        $pivotClass = $query->getPivotClass();
        $pivot = new $pivotClass;

        $treatAsDateField = $filterDescriptor['value'] !== null && in_array($field, $pivot->getDates(), true);

        if ($treatAsDateField && Carbon::parse($filterDescriptor['value'])->toTimeString() === '00:00:00') {
            return $this->searchEngine->applyPivotFieldDateConstraint(
                $query, $field, $filterDescriptor, $or, $request
            );
        }

        if (is_array($filterDescriptor['value'])) {
            return $this->searchEngine->applyPivotFieldInConstraint(
                $query, $field, $filterDescriptor, $or, $request
            );
        }

        return $this->searchEngine->applyPivotFieldConstraint(
            $query, $field, $filterDescriptor, $or, $request
        );
    }

    /**
     * Apply search query to the given query builder based on the "q" query parameter.
     *
     * @param Builder|Relation $query
     * @param Request $request
     * @return Builder|Relation
     */
    public function applySearchingToQuery($query, Request $request)
    {
        if (!$requestedSearchDescriptor = $request->get('search')) {
            return $query;
        }

        $this->paramsValidator->validateSearch($request);

        $searchables = $this->searchEngine->searchableBy();

        $query->where(
            function ($whereQuery) use ($searchables, $requestedSearchDescriptor, $request) {
                $caseSensitive = (bool) Arr::get(
                    $requestedSearchDescriptor,
                    'case_sensitive',
                    config('orion.search.case_sensitive')
                );

                /**
                 * @var Builder $whereQuery
                 */
                foreach ($searchables as $searchable) {
                    if (strpos($searchable, '.') !== false) {
                        $relation = $this->relationsResolver->relationFromParamConstraint($searchable);
                        $relationField = $this->relationsResolver->relationFieldFromParamConstraint($searchable);

                        $whereQuery = $this->searchEngine->applyRelationFieldSearchConstraint(
                            $whereQuery, $relation, $relationField, $caseSensitive, $requestedSearchDescriptor, $request
                        );
                    } else {
                        $whereQuery = $this->searchEngine->applyFieldSearchConstraint(
                            $whereQuery, $searchable, $caseSensitive, $requestedSearchDescriptor, $request
                        );
                    }
                }
            }
        );

        return $query;
    }

    /**
     * Apply sorting to the given query builder based on the "sort" query parameter.
     *
     * @param Builder|Relation $query
     * @param Request $request
     * @return Builder|Relation
     */
    public function applySortingToQuery($query, Request $request)
    {
        $this->paramsValidator->validateSort($request);
        $sortableDescriptors = $request->get('sort', []);

        foreach ($sortableDescriptors as $sortable) {
            $sortableField = $sortable['field'];
            $direction = Arr::get($sortable, 'direction', 'asc');

            if (strpos($sortableField, '.') !== false) {
                $relation = $this->relationsResolver->relationFromParamConstraint($sortableField);
                $relationField = $this->relationsResolver->relationFieldFromParamConstraint($sortableField);

                if ($relation === 'pivot') {
                    $query = $this->searchEngine->applyPivotFieldSorting(
                        $query, $relationField,$direction, $sortable, $request
                    );
                    continue;
                }

                /**
                 * @var Relation $relationInstance
                 */
                $relationInstance = (new $this->resourceModelClass)->{$relation}();

                if ($relationInstance instanceof MorphTo) {
                    continue;
                }

                $query = $this->searchEngine->applyRelationFieldSorting(
                    $query, $relation, $relationField, $direction, $sortable, $request
                );
            } else {
                $query = $this->searchEngine->applyFieldSorting(
                    $query, $direction, $sortable, $request
                );
            }
        }

        return $query;
    }

    /**
     * Apply "soft deletes" query to the given query builder based on either "with_trashed" or "only_trashed" query parameters.
     *
     * @param Builder|Relation|SoftDeletes $query
     * @param Request $request
     * @return Builder|Relation|SoftDeletes
     */
    public function applySoftDeletesToQuery($query, Request $request)
    {
        if (!$query->getMacro('withTrashed')) {
            return $query;
        }

        if (filter_var($request->query('with_trashed', false), FILTER_VALIDATE_BOOLEAN)) {
            return $this->searchEngine->applySoftDeletesWithTrashedConstraint(
                $query, $request
            );
        }

        if (filter_var($request->query('only_trashed', false), FILTER_VALIDATE_BOOLEAN)) {
            return $this->searchEngine->applySoftDeletesOnlyTrashedConstraint(
                $query, $request
            );
        }

        return $query;
    }
}
