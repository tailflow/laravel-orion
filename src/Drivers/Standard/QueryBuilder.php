<?php

namespace Orion\Drivers\Standard;

use Illuminate\Database\Eloquent\Builder;
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
     * @var \Orion\Contracts\SearchBuilder $searchBuilder
     */
    private $searchBuilder;

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
        \Orion\Contracts\SearchBuilder $searchBuilder,
        bool $intermediateMode = false
    ) {
        $this->resourceModelClass = $resourceModelClass;
        $this->paramsValidator = $paramsValidator;
        $this->relationsResolver = $relationsResolver;
        $this->searchBuilder = $searchBuilder;
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
            $query->{$scopeDescriptor['name']}(...Arr::get($scopeDescriptor, 'parameters', []));
        }
    }

    /**
     * Apply filters to the given query builder based on the query parameters.
     *
     * @param Builder|Relation $query
     * @param Request $request
     */
    public function applyFiltersToQuery($query, Request $request): void
    {
        $this->paramsValidator->validateFilters($request);
        $filterDescriptors = $request->get('filters', []);

        foreach ($filterDescriptors as $filterDescriptor) {
            $or = Arr::get($filterDescriptor, 'type', 'and') === 'or';

            if (strpos($filterDescriptor['field'], '.') !== false) {
                $relation = $this->relationsResolver->relationFromParamConstraint($filterDescriptor['field']);
                $relationField = $this->relationsResolver->relationFieldFromParamConstraint($filterDescriptor['field']);

                if ($relation === 'pivot') {
                    $this->buildPivotFilterQueryWhereClause($relationField, $filterDescriptor, $query, $or);
                } else {
                    $query->{$or ? 'orWhereHas' : 'whereHas'}(
                        $relation,
                        function ($relationQuery) use ($relationField, $filterDescriptor) {
                            $this->buildFilterQueryWhereClause($relationField, $filterDescriptor, $relationQuery);
                        }
                    );
                }
            } else {
                $this->buildFilterQueryWhereClause(
                    $this->getQualifiedFieldName($filterDescriptor['field']),
                    $filterDescriptor,
                    $query,
                    $or
                );
            }
        }
    }

    /**
     * Builds filter's query where clause based on the given filterable.
     *
     * @param string $field
     * @param array $filterDescriptor
     * @param Builder|Relation $query
     * @param bool $or
     * @return Builder|Relation
     */
    protected function buildFilterQueryWhereClause(string $field, array $filterDescriptor, $query, bool $or = false)
    {
        if (is_array($filterDescriptor['value']) && in_array(null, $filterDescriptor['value'], true)) {
            $query = $query->{$or ? 'orWhereNull' : 'whereNull'}($field);

            $filterDescriptor['value'] = collect($filterDescriptor['value'])->filter()->values()->toArray();

            if (!count($filterDescriptor['value'])) {
                return $query;
            }
        }

        return $this->buildFilterNestedQueryWhereClause($field, $filterDescriptor, $query, $or);
    }

    /**
     * @param string $field
     * @param array $filterDescriptor
     * @param Builder|Relation $query
     * @param bool $or
     * @return Builder|Relation
     */
    protected function buildFilterNestedQueryWhereClause(
        string $field,
        array $filterDescriptor,
        $query,
        bool $or = false
    ) {
        if ($filterDescriptor['value'] !== null &&
            in_array($filterDescriptor['field'], (new $this->resourceModelClass)->getDates(), true)
        ) {
            $constraint = 'whereDate';
        } else {
            $constraint = 'where';
        }

        if (!is_array($filterDescriptor['value']) || $constraint === 'whereDate') {
            $query->{$or ? 'or' . ucfirst($constraint) : $constraint}(
                $field,
                $filterDescriptor['operator'],
                $filterDescriptor['value']
            );
        } else {
            $query->{$or ? 'orWhereIn' : 'whereIn'}(
                $field,
                $filterDescriptor['value'],
                'and',
                $filterDescriptor['operator'] === 'not in'
            );
        }

        return $query;
    }

    /**
     * Builds filter's pivot query where clause based on the given filterable.
     *
     * @param string $field
     * @param array $filterDescriptor
     * @param Builder|Relation $query
     * @param bool $or
     * @return Builder|Relation
     */
    protected function buildPivotFilterQueryWhereClause(
        string $field,
        array $filterDescriptor,
        $query,
        bool $or = false
    ) {
        if (is_array($filterDescriptor['value']) && in_array(null, $filterDescriptor['value'], true)) {
            $query = $query->{$or ? 'orWherePivotNull' : 'wherePivotNull'}($field);

            $filterDescriptor['value'] = collect($filterDescriptor['value'])->filter()->values()->toArray();

            if (!count($filterDescriptor['value'])) {
                return $query;
            }
        }

        return $this->buildPivotFilterNestedQueryWhereClause($field, $filterDescriptor, $query);
    }

    /**
     * @param string $field
     * @param array $filterDescriptor
     * @param Builder|Relation $query
     * @param bool $or
     * @return Builder
     */
    protected function buildPivotFilterNestedQueryWhereClause(
        string $field,
        array $filterDescriptor,
        $query,
        bool $or = false
    ) {
        if (!is_array($filterDescriptor['value'])) {
            $query->{$or ? 'orWherePivot' : 'wherePivot'}(
                $field,
                $filterDescriptor['operator'],
                $filterDescriptor['value']
            );
        } else {
            $query->{$or ? 'orWherePivotIn' : 'wherePivotIn'}(
                $field,
                $filterDescriptor['value'],
                'and',
                $filterDescriptor['operator'] === 'not in'
            );
        }

        return $query;
    }

    /**
     * Builds a complete field name with table.
     *
     * @param string $field
     * @return string
     */
    protected function getQualifiedFieldName(string $field): string
    {
        $table = (new $this->resourceModelClass)->getTable();
        return "{$table}.{$field}";
    }

    /**
     * Apply search query to the given query builder based on the "q" query parameter.
     *
     * @param Builder|Relation $query
     * @param Request $request
     */
    public function applySearchingToQuery($query, Request $request): void
    {
        if (!$requestedSearchDescriptor = $request->get('search')) {
            return;
        }

        $this->paramsValidator->validateSearch($request);

        $searchables = $this->searchBuilder->searchableBy();

        $query->where(
            function ($whereQuery) use ($searchables, $requestedSearchDescriptor) {
                $requestedSearchString = $requestedSearchDescriptor['value'];
                /**
                 * @var Builder $whereQuery
                 */
                foreach ($searchables as $searchable) {
                    if (strpos($searchable, '.') !== false) {
                        $relation = $this->relationsResolver->relationFromParamConstraint($searchable);
                        $relationField = $this->relationsResolver->relationFieldFromParamConstraint($searchable);

                        $whereQuery->orWhereHas(
                            $relation,
                            function ($relationQuery) use ($relationField, $requestedSearchString) {
                                /**
                                 * @var Builder $relationQuery
                                 */
                                return $relationQuery->where(
                                    $relationField,
                                    'like',
                                    '%' . $requestedSearchString . '%'
                                );
                            }
                        );
                    } else {
                        $whereQuery->orWhere(
                            $this->getQualifiedFieldName($searchable),
                            'like',
                            '%' . $requestedSearchString . '%'
                        );
                    }
                }
            }
        );
    }

    /**
     * Apply sorting to the given query builder based on the "sort" query parameter.
     *
     * @param Builder|Relation $query
     * @param Request $request
     */
    public function applySortingToQuery($query, Request $request): void
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
                    $query->orderByPivot($relationField, $direction);
                    continue;
                }

                /**
                 * @var Relation $relationInstance
                 */
                $relationInstance = (new $this->resourceModelClass)->{$relation}();

                if ($relationInstance instanceof MorphTo) {
                    continue;
                }

                $relationTable = $this->relationsResolver->relationTableFromRelationInstance($relationInstance);
                $relationForeignKey = $this->relationsResolver->relationForeignKeyFromRelationInstance(
                    $relationInstance
                );
                $relationLocalKey = $this->relationsResolver->relationLocalKeyFromRelationInstance($relationInstance);

                $query->leftJoin($relationTable, $relationForeignKey, '=', $relationLocalKey)
                    ->orderBy("$relationTable.$relationField", $direction)
                    ->select($this->getQualifiedFieldName('*'));
            } else {
                $query->orderBy($this->getQualifiedFieldName($sortableField), $direction);
            }
        }
    }

    /**
     * Apply "soft deletes" query to the given query builder based on either "with_trashed" or "only_trashed" query parameters.
     *
     * @param Builder|Relation|SoftDeletes $query
     * @param Request $request
     * @return bool
     */
    public function applySoftDeletesToQuery($query, Request $request): bool
    {
        if (!$query->getMacro('withTrashed')) {
            return false;
        }

        if (filter_var($request->query('with_trashed', false), FILTER_VALIDATE_BOOLEAN)) {
            $query->withTrashed();
        } elseif (filter_var($request->query('only_trashed', false), FILTER_VALIDATE_BOOLEAN)) {
            $query->onlyTrashed();
        }

        return true;
    }
}
