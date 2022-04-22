<?php


namespace Orion\Concerns;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Orion\Contracts\RelationsResolver;
use Orion\Exceptions\CreateResourceException;
use Orion\Exceptions\DestroyResourceException;
use Orion\Exceptions\IndexResourceException;
use Orion\Exceptions\SearchResourceException;
use Orion\Exceptions\UpdateResourceException;
use Orion\Facades\Tracer;
use Orion\Http\Requests\Request;
use Orion\Http\Rules\WhitelistedField;
use ReflectionClass;
use Safe\Exceptions\StringsException;
use RuntimeException;

/**
 * Trait HandlesEloquentOperations
 *
 * @package Orion\Concerns
 */
trait HandlesEloquentOperations
{
    use HandlesAssociation, HandlesTransactions;

    /**
     * @var string $MODEL_PATH
     */
    private string $MODEL_PATH = 'App\\Models\\';


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
        $treatAsDateField = $filterDescriptor['value'] !== null &&
            in_array($filterDescriptor['field'], (new $this->model)->getDates(), true);

        if ($treatAsDateField && Carbon::parse($filterDescriptor['value'])->toTimeString() === '00:00:00') {
            $constraint = 'whereDate';
        } else {
            $constraint = 'where';
        }

        if (!is_array($filterDescriptor['value']) || $constraint === 'whereDate') {
            $query->{$or ? 'or' . ucfirst($constraint) : $constraint}(
                $field,
                $filterDescriptor['operator'] ?? '=',
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
     * Builds a complete field name with table.
     *
     * @param string $field
     * @return string
     */
    protected function getQualifiedFieldName(string $field): string
    {
        $table = (new $this->model)->getTable();
        return "{$table}.{$field}";
    }

    /**
     * @throws ValidationException
     * @throws SearchResourceException
     */
    public function search(array $input): LengthAwarePaginator|Collection|array
    {
        $result = null;

        /**
         * @var Model $instance
         */
        $instance = new $this->model;
        $query = $instance::query();

        $sortables = $instance->sortables ?? [];
        $filters = $instance->filters ?? [];
        Validator::make(
            $input,
            [
                'scopes'              => ['sometimes', 'array'],
                'scopes.*.name'       => ['required_with:scopes', 'in:' . implode(',', $input['exposedScopes'] ?? [])],
                'scopes.*.parameters' => ['sometimes', 'array'],

                'filters'            => ['sometimes', 'array'],
                'filters.*.type'     => ['sometimes', 'in:and,or'],
                'filters.*.field'    => ['required_with:filters', 'regex:/^[\w.\_\-\>]+$/', new WhitelistedField($filters)],
                'filters.*.operator' => ['sometimes', 'in:<,<=,>,>=,=,!=,like,not like,ilike,not ilike,in,not in'],
                'filters.*.value'    => ['present', 'nullable'],

                'search'                => ['sometimes', 'array'],
                'search.value'          => ['string', 'nullable'],
                'search.case_sensitive' => ['sometimes', 'bool'],

                'sort'             => ['sometimes', 'array'],
                'sort.*.field'     => ['required_with:sort', 'regex:/^[\w.\_\-\>]+$/', new WhitelistedField($sortables)],
                'sort.*.direction' => ['sometimes', 'in:asc,desc'],
            ]
        )->validate();


        $scopeDescriptors = $input['scopes'] ?? [];

        foreach ($scopeDescriptors as $scopeDescriptor) {
            $query->{$scopeDescriptor['name']}(...Arr::get($scopeDescriptor, 'parameters', []));
        }

        $filterDescriptors = $input['filters'] ?? [];
        $relationResolver = App::makeWith(
            RelationsResolver::class,
            [
                'includableRelations'     => $input['requestedRelations'] ?? [],
                'alwaysIncludedRelations' => $input['alwaysIncludes'] ?? [],
            ]
        );

        foreach ($filterDescriptors as $filterDescriptor) {
            $or = Arr::get($filterDescriptor, 'type', 'and') === 'or';

            if (strpos($filterDescriptor['field'], '.') !== false) {
                $relation = $relationResolver->relationFromParamConstraint($filterDescriptor['field']);
                $relationField = $relationResolver->relationFieldFromParamConstraint($filterDescriptor['field']);
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

            }else {
                $this->buildFilterQueryWhereClause(
                    $this->getQualifiedFieldName($filterDescriptor['field']),
                    $filterDescriptor,
                    $query,
                    $or
                );
            }
        }

        $requestedSearchDescriptor = $input['search'] ?? [];
        $searchables = $instance->searchables ?? [];

        if (count($requestedSearchDescriptor) > 0) {
            $query->where(
                function ($whereQuery) use ($instance, $relationResolver, $searchables, $requestedSearchDescriptor) {
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
                            $relation = $relationResolver->relationFromParamConstraint($searchable);
                            $relationField = $relationResolver->relationFieldFromParamConstraint($searchable);

                            $whereQuery->orWhereHas(
                                $relation,
                                function ($relationQuery) use ($relationField, $requestedSearchString, $caseSensitive) {
                                    /**
                                     * @var Builder $relationQuery
                                     */
                                    if (!$caseSensitive) {
                                        return $relationQuery->whereRaw(
                                            "lower({$relationField}) like lower(?)",
                                            ['%' . $requestedSearchString . '%']
                                        );
                                    }

                                    return $relationQuery->where(
                                        $relationField,
                                        'like',
                                        '%' . $requestedSearchString . '%'
                                    );
                                }
                            );
                        } else {
                            $qualifiedFieldName = $this->getQualifiedFieldName($searchable);

                            if (!$caseSensitive) {
                                $whereQuery->orWhereRaw(
                                    "lower({$qualifiedFieldName}) like lower(?)",
                                    ['%' . $requestedSearchString . '%']
                                );
                            } else {
                                $whereQuery->orWhere(
                                    $qualifiedFieldName,
                                    'like',
                                    '%' . $requestedSearchString . '%'
                                );
                            }
                        }

                    }
                }
            );
        }

        $sortableDescriptors = $input['sort'] ?? [];

        foreach ($sortableDescriptors as $sortable) {
            $sortableField = $sortable['field'];
            $direction = Arr::get($sortable, 'direction', 'asc');

            if (strpos($sortableField, '.') !== false) {

                $relation = $relationResolver->relationFromParamConstraint($sortableField);
                $relationField = $relationResolver->relationFieldFromParamConstraint($sortableField);

                if ($relation === 'pivot') {
                    $query->orderByPivot($relationField, $direction);
                    continue;
                }

                /**
                 * @var Relation $relationInstance
                 */
                $relationInstance = (new $instance)->{$relation}();

                if ($relationInstance instanceof MorphTo) {
                    continue;
                }

                $relationTable = $relationResolver->relationTableFromRelationInstance($relationInstance);
                $relationForeignKey = $relationResolver->relationForeignKeyFromRelationInstance($relationInstance);
                $relationLocalKey = $relationResolver->relationLocalKeyFromRelationInstance($relationInstance);
                $table = (new $instance)->getTable();
                $query->leftJoin($relationTable, $relationForeignKey, '=', $relationLocalKey)
                    ->orderBy("$relationTable.$relationField", $direction)
                    ->select("{$table}.*");
            } else {
                $table = (new $instance)->getTable();
                $query->orderBy("{$table}.{$sortableField}", $direction);
            }
        }

        if ($query->getMacro('withTrashed')) {
            if (filter_var($input['with_trashed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $query->withTrashed();
            } elseif (filter_var($input['only_trashed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $query->onlyTrashed();
            }
        }

        $defaultLimit = config('orion.api.limit', 10);
        $limit = $input['limit'] ?? $defaultLimit;

        $paginationDisabled = $input['paginationDisabled'] ?? config('orion.api.pagination_disabled', false);

        if ($paginationDisabled) {
            $result = $query->get();
        } else {
            $result = $query->paginate($limit);
        }

        //TODO make facade for RelationshipResolver
        $guard = $input['guard'] ?? false;
        if (!in_array($guard, [true, false])) {
            throw new SearchResourceException(
                'SEARCH_GUARD_VALIDATION_FAILED',
                $guard
            );
        }
        if ($guard) {
            $relationResolver->guardRelationsForCollection(
                $result instanceof Paginator ? $result->getCollection() : $result,
                $input['requestedRelations']
            );
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function list(array $params = [], $perPage = 10): LengthAwarePaginator|Collection|array
    {

        $result = null;

        /**
         * @var Model $instance
         */
        $instance = new $this->model;
        $query = $instance::query() ?? null;

        if ($query == null) {
            throw new IndexResourceException(
                'INDEX_QUERY_BUILDER_FAILED'
            );
        }


        if ($query->getMacro('withTrashed')) {
            if (filter_var($params['with_trashed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $query->withTrashed();
            } elseif (filter_var($params['only_trashed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $query->onlyTrashed();
            }
        }

        $defaultLimit = config('orion.api.limit', 10);
        $limit = $params['limit'] ?? $defaultLimit;

        $paginationDisabled = $params['paginationDisabled'] ?? config('orion.api.pagination_disabled', false);

        if ($paginationDisabled) {
            $result = $query->get();
        } elseif ($params['hierarchical'] ?? null) {

            $result = (new (config('permission.models.' . $params['hierarchical'])))::query()->where(
                $params['hierarchical'] === 'role' ? [] : [
                    'name' => $this->associate($this->model, 0) . 'Group',
                ]
            )->{(function () use ($params) {
                return $params['hierarchical'] === 'role' ? 'get' : 'first';
            })()}();

            $result = ['data' => $params['hierarchical'] === 'role' ? $result->toTree() : $result->descendants->toTree()];

        } else {
            $result = $query->paginate($limit);
        }

        $guard = $params['guard'] ?? false;
        if (!in_array($guard, [true, false])) {
            throw new IndexResourceException(
                'INDEX_GUARD_VALIDATION_FAILED()',
                $guard
            );
        }
        if ($guard) {
            $relationResolver = App::makeWith(
                RelationsResolver::class,
                [
                    'includableRelations'     => $params['requestedRelations'],
                    'alwaysIncludedRelations' => $params['alwaysIncludes'] ?? [],
                ]
            );

            $relationResolver->guardRelationsForCollection(
                $result instanceof Paginator ? $result->getCollection() : $result,
                $params['requestedRelations']
            );
        }

        return $result;

    }

    /**
     * Get eloquent resource by id.
     *
     *
     * @param array $params
     *
     * @return Model
     * @throws Exception
     */

    public function getById(array $params): Model|array
    {
        /**
         * @var Model $instance
         */
        $instance = new $this->model;
        $query = $instance::query();

        if ($query->getMacro('withTrashed')) {
            if (filter_var($params['with_trashed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $query->withTrashed();
            } elseif (filter_var($params['only_trashed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $query->onlyTrashed();
            }
        }

        $id = $params['id'];

        if ($params['hierarchical'] ?? null) {

            $result = (new (config('permission.models.' . $params['hierarchical'])))::query()->where(
                $params['hierarchical'] === 'role' ? ['id' => $id] : []
            )->{(function () use ($params) {
                return $params['hierarchical'] === 'role' ? 'first' : 'get';
            })()}();

            $result = ['data' => $params['hierarchical'] === 'role' ? $result->permissions->toTree() : $result->descendants->toTree()];

        } else {
            $result = $query
                ->where('id', $id)
                ->firstOrFail();
        }


        $guard = $params['guard'] ?? false;
        if (!in_array($guard, [true, false])) {
            throw new IndexResourceException(
                'SHOW_GUARD_VALIDATION_FAILED',
                $guard
            );
        }
        if ($guard) {
            $relationResolver = App::makeWith(
                RelationsResolver::class,
                [
                    'includableRelations'     => $params['requestedRelations'],
                    'alwaysIncludedRelations' => $params['alwaysIncludes'] ?? [],
                ]
            );
            $relationResolver->guardRelations(
                $result,
                $params['requestedRelations']
            );
        }

        return $result;
    }

    /**
     * Apply "soft deletes" query to the given query builder based on either "with_trashed" or "only_trashed" query parameters.
     *
     * @param Builder|Relation|SoftDeletes $query
     * @param Request                      $request
     *
     * @return bool
     */
    private function applySoftDeletesToQuery($query, Request $request): bool
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
     * Create a new eloquent resource.
     *
     *
     * @param array $request
     *
     * @return Model
     */
    public function create(array $request): Model
    {
        try {
            $this->startTransaction();

            $result = $this->createModel($request);
            $this->trace($result->id, 'CREATED');

            $this->commitTransaction();

        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }

        return $result;
    }

    /**
     * @param $id
     * @param $action
     */
    function trace($id, $action)
    {
        $input = [
            'id'     => $id,
            'action' => $action,
            'model'  => $this->model,
        ];
        Tracer::trace($input);
    }

    /**
     * Create a new eloquent resource.
     *
     *
     * @param array $input
     *
     * @return Model
     * @throws CreateResourceException
     * @throws StringsException
     */

    private function createModel(array $input): Model
    {
        $result = null;
        /**
         * @var $entity Model
         */
        $entity = new $this->model;
        $entity->fill(
            Arr::except($input, array_keys($entity->getDirty()))
        );

        $entity->save();
        $entity = $entity->fresh();
        $entity->wasRecentlyCreated = true;

        $relationships = array_keys($entity->getRelations());

        foreach ($relationships as $relationship) {

            $isHasOneRelation = true;
            $relationshipTableName = $relationship;
            if (Pluralizer::singular($relationship) !== $relationship) {
                $relationshipTableName = Str::pluralStudly(class_basename($relationship));
                $isHasOneRelation = false;
            }
            $relationshipTableName = Str::snake($relationshipTableName);
            $relatedEntities = $input[$relationshipTableName] ?? null;
            $fk = Str::snake(sprintf("%s_id", class_basename($entity)));
            if ($relatedEntities) {
                $relatedEntities = $isHasOneRelation ? [$relatedEntities] : $relatedEntities;

                foreach ($relatedEntities as $relatedEntity) {

                    $relatedModel = null;
                    $relatedModel = tap($relatedModel, function (&$model) use ($relationship) {
                        //TODO use associate method
                        $isValid = class_exists((sprintf('%s%s', $this->MODEL_PATH, Pluralizer::singular($relationship))));
                        $model = $isValid ? new (sprintf('%s%s', $this->MODEL_PATH, Pluralizer::singular($relationship)))() : null;
                    });
                    if ($relatedModel != null) {
                        if ($this->hasRelevance(1, $relatedModel)) {
                            $entity->{$relationship}()->attach($relatedEntity);
                        } else {
                            $fillArray = Arr::except($relatedEntity, array_keys($relatedModel->getDirty()));
                            $fillArray[$fk] = $entity->id;
                            $relatedModel->fill($fillArray);
                            $relatedModel->save();
                        }
                    }
                }
            }
        }

        $entity = $entity->fresh();
        $result = $entity;

        $guard = $input['guard'] ?? false;
        if (!in_array($guard, [true, false])) {
            throw new CreateResourceException(
                'CREATE_GUARD_VALIDATION_FAILED',
                $guard
            );
        }
        if ($guard) {
            $relationResolver = App::makeWith(
                RelationsResolver::class,
                [
                    'includableRelations'     => $input['requestedRelations'],
                    'alwaysIncludedRelations' => $input['alwaysIncludes'] ?? [],
                ]
            );

            $relationResolver->guardRelations(
                $result,
                $input['requestedRelations']
            );
        }

        return $result;
    }

    /**
     * @param int $relevance
     *
     * @return bool
     * @throws \ReflectionException
     */
    private function hasRelevance(int $relevance, $relatedEntity)
    {
        $result = false;
        $reflection = new ReflectionClass($this->model);
        $attributes = $reflection->getAttributes();
        if ($attributes != null) {
            $key = array_keys($attributes[0]->newInstance()->input)[$relevance] ?? null;
            if ($key) {
                $checkString = $attributes[0]->newInstance()->input[$key][0] ?? null;
                $result = str_contains($checkString, class_basename($relatedEntity));
            }
        }

        return $result;
    }

    /**
     * @param array $request
     *
     * @return Model
     * @throws CreateResourceException
     * @throws StringsException
     */
    public function seed(array $request): Model
    {
        $result = $this->createModel($request);

        return $result;
    }

    /**
     * @param array $request
     *
     * @return Model
     * @throws CreateResourceException
     * @throws StringsException
     */
    public function touch(array $request): Model
    {
        $result = $this->createModel($request);
        return $result;
    }

    /**
     * Update the eloquent resource by id.
     *
     *
     * @param array $input
     *
     * @return Model
     * @throws Exception
     */
    public function update(array $input): Model
    {
        $result = null;

        try {
            $this->startTransaction();

            $id = $input['id'];
            /**
             * @var Model $instance
             */
            $entity = $this->model::query()->findOrFail($id);

            $entity->fill(
                Arr::except($input, array_keys($entity->getDirty()))
            );

            $entity->save();
            $entity = $entity->fresh();

            $relationships = array_keys($entity->getRelations());

            foreach ($relationships as $relationship) {
                $isHasOneRelation = true;
                $relationshipTableName = $relationship;
                if (Pluralizer::singular($relationship) !== $relationship) {
                    $relationshipTableName = Str::pluralStudly(class_basename($relationship));
                    $isHasOneRelation = false;
                }
                $relationshipTableName = Str::snake($relationshipTableName);
                $relatedEntities = $input[$relationshipTableName] ?? null;
                $fk = Str::snake(sprintf("%s_id", class_basename($entity)));
                if ($relatedEntities || (is_array($relatedEntities) && sizeof($relatedEntities) === 0)) {
                    if (empty($relatedEntities)) {
                        $entity->{$relationship}()->sync([]);
                    } elseif ($this->is_all_numeric($relatedEntities)) {
                        $entity->{$relationship}()->sync($relatedEntities);
                    } else {
                        $relatedEntities = $isHasOneRelation ? [$relatedEntities] : $relatedEntities;
                        foreach ($relatedEntities as $relatedEntity) {
                            if ($isHasOneRelation && is_numeric($relatedEntity)) {

                            } else {
                                $relatedModel = null;
                                /**
                                 * @var Model $relatedModel
                                 */
                                $relatedModel = tap($relatedModel, function (&$model) use ($relationship) {
                                    //TODO use associate method / config
                                    $model = new (sprintf('%s%s', $this->MODEL_PATH, Pluralizer::singular($relationship)))();
                                });
                                if ($relatedModel == null) {
                                    throw new UpdateResourceException(
                                        'UPDATE_RELATIONSHIP_API_MODEL_FAILED',
                                        $relationship
                                    );
                                }
                                $fillArray = Arr::except($relatedEntity, array_keys($relatedModel->getDirty()));
                                $fillArray[$fk] = $entity->id;
                                $relatedModel::updateOrCreate(['id' => $fillArray['id'] ?? null], $fillArray);
                            }
                        }
                    }
                }
            }

            $entity = $entity->fresh();

            $guard = $input['guard'] ?? false;
            if (!in_array($guard, [true, false])) {
                throw new UpdateResourceException(
                    'UPDATE_GUARD_VALIDATION_FAILED',
                    $guard
                );
            }
            if ($guard) {
                $relationResolver = App::makeWith(
                    RelationsResolver::class,
                    [
                        'includableRelations'     => $input['requestedRelations'],
                        'alwaysIncludedRelations' => $input['alwaysIncludes'] ?? [],
                    ]
                );

                $relationResolver->guardRelations(
                    $entity,
                    $input['requestedRelations']
                );
            }

            $result = $entity;
            $this->trace($result->id, 'UPDATED');

            $this->commitTransaction();

        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }

        return $result;
    }

    /**
     * @throws DestroyResourceException
     */
    public function delete(array $input): Model
    {
        try{
            $result = null;
            $this->startTransaction();
            $id = $input['id'];
            $softDeletes = method_exists(new $this->model, 'trashed');
            $forceDeletes = $softDeletes && filter_var($input['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $query = $this->model::query()->when(
                $softDeletes,
                function ($query) {
                    $query->withTrashed();
                }
            );
            /**
             * @var Model $entity
             */
            $entity = $query->findOrFail($id);

            $isResourceTrashed = !$forceDeletes && $softDeletes && $entity->trashed();
            if ($isResourceTrashed) {
                abort(404);
            }

            if ($forceDeletes) {
                $entity->forceDelete();
            } else {
                $entity->delete();
                if ($softDeletes) {
                    $entity = $entity->fresh();
                }
            }

            $guard = $input['guard'] ?? false;
            if (!in_array($guard, [true, false])) {
                throw new DestroyResourceException(
                    'DESTROY_GUARD_VALIDATION_FAILED',
                    $guard
                );
            }
            if ($guard) {
                $relationResolver = App::makeWith(
                    RelationsResolver::class,
                    [
                        'includableRelations'     => $input['requestedRelations'],
                        'alwaysIncludedRelations' => $input['alwaysIncludes'] ?? [],
                    ]
                );

                $relationResolver->guardRelations(
                    $entity,
                    $input['requestedRelations']
                );
            }
            $result = $entity;
            $this->trace($id, 'DELETED');
            $this->commitTransaction();
            return $result;

        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * @return mixed
     */
    public function getAvailability()
    {

        /**
         * @var Model $model
         */
        $model = new $this->model;
        $tableName = $model->getTable();
        $id = DB::select("SHOW TABLE STATUS LIKE '$tableName'");
        $result = $id[0]->Auto_increment;

        return $result;
    }

    /**
     * @param string $model
     *
     * @return $this
     */
    public function setModel(string $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @param int|array $array
     *
     * @return bool
     */
    private function is_all_numeric(int|array $input)
    {
        $result = true;
        if (is_numeric($input)) {
            $result = false;
        } else {
            foreach ($input as $item) {
                if (!is_numeric($item)) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }
}
