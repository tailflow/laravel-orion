<?php

namespace Orion\Drivers\Standard;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonException;
use Orion\Http\Requests\Request;
use RuntimeException;

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
     * @throws JsonException
     */
    public function buildQuery($query, Request $request)
    {
        $actionMethod = $request->route()->getActionMethod();

        if (!$this->intermediateMode) {
            if (in_array($actionMethod, ['index', 'search', 'show'])) {
                if ($actionMethod === 'search') {
                    $this->applyScopesToQuery($query, $request);
                    $this->applyFiltersToQuery($query, $request);
                    $this->applySearchingToQuery($query, $request);
                    $this->applySortingToQuery($query, $request);
                }
                $this->applySoftDeletesToQuery($query, $request);
            }

            $this->applyIncludesToQuery($query, $request);
            $this->applyAggregatesToQuery($query, $request);
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
     * @param array $filterDescriptors
     * @throws JsonException
     */
    public function applyFiltersToQuery($query, Request $request, array $filterDescriptors = []): void
    {
        if (!$filterDescriptors && !$this->intermediateMode) {
            $this->paramsValidator->validateFilters($request);

            $filterDescriptors = $request->get('filters', []);
        }

        foreach ($filterDescriptors as $filterDescriptor) {
            $or = Arr::get($filterDescriptor, 'type', 'and') === 'or';

            if (is_array($childrenDescriptors = Arr::get($filterDescriptor, 'nested'))) {
                $query->{$or ? 'orWhere' : 'where'}(function ($query) use ($request, $childrenDescriptors) {
                    $this->applyFiltersToQuery($query, $request, $childrenDescriptors);
                });
            } elseif (strpos($filterDescriptor['field'], '.') !== false) {
                $relation = $this->relationsResolver->relationFromParamConstraint($filterDescriptor['field']);
                $relationField = $this->relationsResolver->relationFieldFromParamConstraint($filterDescriptor['field']);

                if ($relation === 'pivot') {
                    $this->buildPivotFilterQueryWhereClause($relationField, $filterDescriptor, $query, $or);
                } else {
                    $relationInstance = (new $this->resourceModelClass)->{$relation}();

                    $qualifiedRelationFieldName = $this->relationsResolver->getQualifiedRelationFieldName($relationInstance, $relationField);

                    $query->{$or ? 'orWhereHas' : 'whereHas'}(
                        $relation,
                        function ($relationQuery) use ($qualifiedRelationFieldName, $filterDescriptor) {
                            $this->buildFilterQueryWhereClause($qualifiedRelationFieldName, $filterDescriptor, $relationQuery);
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
     * @throws JsonException
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
     * @throws JsonException
     */
    protected function buildFilterNestedQueryWhereClause(
        string $field,
        array $filterDescriptor,
        $query,
        bool $or = false
    ) {
        $treatAsDateField = $filterDescriptor['value'] !== null &&
            in_array($filterDescriptor['field'], (new $this->resourceModelClass)->getDates(), true);

        if ($treatAsDateField && Carbon::parse($filterDescriptor['value'])->toTimeString() === '00:00:00') {
            $constraint = 'whereDate';
        } elseif (in_array(Arr::get($filterDescriptor, 'operator'), ['all in', 'any in'])) {
            $constraint = 'whereJsonContains';
        } else {
            $constraint = 'where';
        }

        if ($constraint !== 'whereJsonContains' && (!is_array(
                    $filterDescriptor['value']
                ) || $constraint === 'whereDate')) {
            $query->{$or ? 'or'.ucfirst($constraint) : $constraint}(
                $field,
                $filterDescriptor['operator'] ?? '=',
                $filterDescriptor['value']
            );
        } elseif ($constraint === 'whereJsonContains') {
            if (!is_array($filterDescriptor['value'])) {
                $query->{$or ? 'orWhereJsonContains' : 'whereJsonContains'}(
                    $field,
                    $filterDescriptor['value']
                );
            } else {
                $query->{$or ? 'orWhere' : 'where'}(function ($nestedQuery) use ($filterDescriptor, $field) {
                    foreach ($filterDescriptor['value'] as $value) {
                        $nestedQuery->{$filterDescriptor['operator'] === 'any in' ? 'orWhereJsonContains' : 'whereJsonContains'}(
                            $field,
                            $value
                        );
                    }
                });
            }
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
            if ((float) app()->version() <= 7.0) {
                throw new RuntimeException(
                    "Filtering by nullable pivot fields is only supported for Laravel version > 8.0"
                );
            }

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
     * @param Builder|BelongsToMany $query
     * @param bool $or
     * @return Builder
     */
    protected function buildPivotFilterNestedQueryWhereClause(
        string $field,
        array $filterDescriptor,
        $query,
        bool $or = false
    ) {
        $pivotClass = $query->getPivotClass();
        $pivot = new $pivotClass;

        $treatAsDateField = $filterDescriptor['value'] !== null && in_array($field, $pivot->getDates(), true);

        if ($treatAsDateField && Carbon::parse($filterDescriptor['value'])->toTimeString() === '00:00:00') {
            $query->addNestedWhereQuery(
                $query->newPivotStatement()->whereDate(
                    $query->getTable().".{$field}",
                    $filterDescriptor['operator'] ?? '=',
                    $filterDescriptor['value']
                )
            );
        } elseif (!is_array($filterDescriptor['value'])) {
            $query->{$or ? 'orWherePivot' : 'wherePivot'}(
                $field,
                $filterDescriptor['operator'] ?? '=',
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
    public function getQualifiedFieldName(string $field): string
    {
        $table = (new $this->resourceModelClass)->getTable();

        return "{$table}.{$field}";
    }

    /**
     * Get the model class from a given relation.
     *
     * @param string $relation
     * @return string
     */
    public function getRelationModelClass(string $relation): ?string
    {
        $relations = collect(explode('.', $relation));

        $resourceModel = (new $this->resourceModelClass);

        foreach ($relations as $nestedRelation) {
            if ((float) app()->version() >= 8.0) {
                if (!$resourceModel->isRelation($nestedRelation)) {
                    return null;
                }
            } elseif (!method_exists($resourceModel, $nestedRelation)) {
                return null;
            }

            if ($resourceModel->$nestedRelation() instanceof MorphTo) {
                return MorphTo::class;
            }

            $resourceModel = $resourceModel->$nestedRelation()->getModel();
        }

        return get_class($resourceModel);
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

                        $relationInstance = (new $this->resourceModelClass)->{$relation}();

                        $qualifiedRelationFieldName = $this->relationsResolver->getQualifiedRelationFieldName($relationInstance, $relationField);

                        $whereQuery->orWhereHas(
                            $relation,
                            function ($relationQuery) use ($qualifiedRelationFieldName, $requestedSearchString, $caseSensitive) {
                                /**
                                 * @var Builder $relationQuery
                                 */
                                if (!$caseSensitive) {
                                    return $relationQuery->whereRaw(
                                        "lower({$qualifiedRelationFieldName}) like lower(?)",
                                        ['%'.$requestedSearchString.'%']
                                    );
                                }

                                return $relationQuery->where(
                                    $qualifiedRelationFieldName,
                                    'like',
                                    '%'.$requestedSearchString.'%'
                                );
                            }
                        );
                    } else {
                        $qualifiedFieldName = $this->getQualifiedFieldName($searchable);

                        if (!$caseSensitive) {
                            $whereQuery->orWhereRaw(
                                "lower({$qualifiedFieldName}) like lower(?)",
                                ['%'.$requestedSearchString.'%']
                            );
                        } else {
                            $whereQuery->orWhere(
                                $qualifiedFieldName,
                                'like',
                                '%'.$requestedSearchString.'%'
                            );
                        }
                    }
                }
            }
        );
    }

    /**
     * Apply sorting to the given query builder based on the "sort" query parameter.
     *
     * @param Builder $query
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

                $requiresJoin = collect($query->toBase()->joins ?? [])
                    ->where('table', $relationTable)->isEmpty();

                if ($requiresJoin) {
                    $query->leftJoin($relationTable, $relationForeignKey, '=', $relationLocalKey);
                }

                $qualifiedRelationFieldName = $this->relationsResolver->getQualifiedRelationFieldName($relationInstance,  $relationField);

                $query->orderBy($qualifiedRelationFieldName, $direction)
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


    /**
     * Apply eager loading of aggregates to the query.
     *
     * @param Builder|Relation|SoftDeletes $query
     * @param Request $request
     * @param array $aggregateDescriptors
     * @return void
     */
    public function applyAggregatesToQuery($query, Request $request, array $aggregateDescriptors = []): void
    {
        if (!$aggregateDescriptors) {
            $this->paramsValidator->validateAggregators($request);

            $aggregateDescriptors = collect();
            // Here we regroup query and post params on the same format
            foreach (['count', 'min', 'max', 'avg', 'sum', 'exists'] as $aggregateFunction) {
                $aggregateDescriptors = $aggregateDescriptors->merge(
                    collect(explode(',', $request->query("with_$aggregateFunction", '')))
                        ->filter()
                        ->map(function ($include) use ($aggregateFunction) {
                            $explodedInclude = explode('.', $include);
                            return [
                                'relation' => $explodedInclude[0],
                                'field' => $explodedInclude[1] ?? '*',
                                'type' => $aggregateFunction,
                            ];
                        })->all()
                );
            }

            $aggregateDescriptors = $aggregateDescriptors->merge($request->get('aggregates', []));
        }

        foreach ($aggregateDescriptors as $aggregateDescriptor) {
            if ((float) app()->version() < 8.0) {
                throw new RuntimeException(
                    "Aggregate queries are only supported with Laravel 8 and later"
                );
            }

            if (!$relationModelClass = $this->getRelationModelClass($aggregateDescriptor['relation'])) {
                continue;
            }

            if ($relationModelClass === MorphTo::class) {
                $query->withAggregate(
                    $aggregateDescriptor['relation'], $aggregateDescriptor['field'] ?? '*', $aggregateDescriptor['type']
                );

                continue;
            }

            $query->withAggregate([
                $aggregateDescriptor['relation'] => function (Builder $aggregateQuery) use (
                    $aggregateDescriptor,
                    $request,
                    $relationModelClass
                ) {
                    $relationQueryBuilder = $this->clone($relationModelClass);

                    $relationQueryBuilder->applyFiltersToQuery(
                        $aggregateQuery,
                        $request,
                        $this->removeFieldPrefixFromFields(
                            $aggregateDescriptor['filters'] ?? [],
                            $aggregateDescriptor['relation'].'.'
                        )
                    );
                },
            ], $aggregateDescriptor['field'] ?? '*', $aggregateDescriptor['type']);
        }
    }


    /**
     * Apply eager loading relations to the query.
     *
     * @param Builder|Relation|SoftDeletes $query
     * @param Request $request
     * @param array $includeDescriptors
     * @return void
     */
    public function applyIncludesToQuery($query, Request $request, array $includeDescriptors = []): void
    {
        if (!$includeDescriptors) {
            $this->paramsValidator->validateIncludes($request);

            $requestedIncludeDescriptors = collect($request->get('includes', []));

            $includeDescriptors = collect($this->relationsResolver->requestedRelations($request))
                ->map(function ($include) use ($requestedIncludeDescriptors) {
                    $requestedIncludeDescriptor = $requestedIncludeDescriptors
                        ->where('relation', $include)
                        ->first();

                    return $requestedIncludeDescriptor ?? ['relation' => $include];
                })->toArray();
        }

        foreach ($includeDescriptors as $includeDescriptor) {
            if (!$relationModelClass = $this->getRelationModelClass($includeDescriptor['relation'])) {
                continue;
            }

            if ($relationModelClass === MorphTo::class) {
                $query->with($includeDescriptor['relation']);

                continue;
            }

            $query->with([
                $includeDescriptor['relation'] => function (Relation $includeQuery) use (
                    $includeDescriptor,
                    $request,
                    $relationModelClass
                ) {
                    $relationQueryBuilder = $this->clone($relationModelClass);

                    if(array_key_exists("limit", $includeDescriptor)) {
                        $includeQuery->take($includeDescriptor["limit"]);
                    }

                    $relationQueryBuilder->applyFiltersToQuery(
                        $includeQuery,
                        $request,
                        $this->removeFieldPrefixFromFields(
                            $includeDescriptor['filters'] ?? [],
                            $includeDescriptor['relation'].'.'
                        )
                    );
                },
            ]);
        }
    }

    public function clone(string $resourceModelClass): self
    {
        return new static(
            $resourceModelClass, $this->paramsValidator, $this->relationsResolver, $this->searchBuilder, true
        );
    }

    protected function removeFieldPrefixFromFields(array $array, string $search)
    {
        return collect($array)
            ->transform(function ($item) use ($search) {
                if (isset($item['nested'])) {
                    $item['nested'] = $this->removeFieldPrefixFromFields($item['nested'], $search);
                } else {
                    $item['field'] = Str::replaceFirst($search, '', $item['field']);
                }

                return $item;
            })
            ->all();
    }
}
