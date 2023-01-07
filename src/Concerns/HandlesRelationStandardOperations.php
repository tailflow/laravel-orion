<?php

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\CollectionResource;
use Orion\Http\Resources\Resource;

trait HandlesRelationStandardOperations
{
    /**
     * Fetch the list of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     */
    public function index(Request $request, $parentKey)
    {
        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $parentQuery = $this->buildIndexParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runIndexParentFetchQuery($request, $parentQuery, $parentKey);

        $this->authorize($this->resolveAbility('index'), [$this->resolveResourceModelClass(), $parentEntity]);

        $beforeHookResult = $this->beforeIndex($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize($this->resolveAbility('show'), $parentEntity);

        $query = $this->buildIndexFetchQuery($request, $parentEntity, $requestedRelations);
        $entities = $this->runIndexFetchQuery(
            $request,
            $query,
            $parentEntity,
            $this->paginator->resolvePaginationLimit($request)
        );

        ($entities instanceof Paginator ? $entities->getCollection() : $entities)->transform(
            function ($entity) {
                $entity = $this->cleanupEntity($entity);

                if (count($this->getPivotJson())) {
                    $entity = $this->castPivotJsonFields($entity);
                }

                return $entity;
            }
        );

        $afterHookResult = $this->afterIndex($request, $parentEntity, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection(
            $entities instanceof Paginator ? $entities->getCollection() : $entities,
            $requestedRelations
        );

        return $this->collectionResponse($entities);
    }

    /**
     * The hooks is executed before fetching the list of relation resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return mixed
     */
    protected function beforeIndex(Request $request, Model $parentEntity)
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in index method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildIndexParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching parent entity.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->queryBuilder->buildQuery($this->newModelQuery(), $request);
    }

    /**
     * Runs the given query for fetching parent entity in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runIndexParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity.
     *
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $query->where($this->resolveQualifiedParentKeyName(), $parentKey)->firstOrFail();
    }

    /**
     * Builds Eloquent query for fetching relation entities in index method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildIndexFetchQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        $filters = collect($request->get('filters', []))
            ->map(function (array $filterDescriptor) use ($request, $parentEntity) {
                return $this->beforeFilterApplied($request, $parentEntity, $filterDescriptor);
            })->toArray();

        $request->request->add(['filters' => $filters]);

        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations);
    }

    /**
     * Wrapper function to build Eloquent query for fetching relation entity.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildRelationFetchQuery(
        Request $request,
        Model $parentEntity,
        array $requestedRelations
    ): Relation {
        return $this->buildRelationFetchQueryBase(
            $request,
            $parentEntity,
            $requestedRelations
        );
    }

    /**
     * Builds Eloquent query for fetching relation entity.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildRelationFetchQueryBase(
        Request $request,
        Model $parentEntity,
        array $requestedRelations
    ): Relation {
        return $this->relationQueryBuilder->buildQuery(
            $this->newRelationQuery($parentEntity), $request
        );
    }

    /**
     * Runs the given query for fetching relation entities in index method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @param int $paginationLimit
     * @return Paginator|Collection
     */
    protected function runIndexFetchQuery(Request $request, Relation $query, Model $parentEntity, int $paginationLimit)
    {
        return $this->shouldPaginate($request, $paginationLimit) ? $query->paginate($paginationLimit) : $query->get();
    }

    /**
     * Removes unrelated to model attributes, if any.
     *
     * @param Model $entity
     * @return Model
     */
    protected function cleanupEntity(Model $entity)
    {
        $entity->makeHidden('laravel_through_key');

        return $entity;
    }

    /**
     * The hooks is executed after fetching the list of relation resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Paginator|Collection $entities
     * @return mixed
     */
    protected function afterIndex(Request $request, Model $parentEntity, $entities)
    {
        return null;
    }

    /**
     * Filters, sorts, and fetches the list of resources.
     *
     * @param Request $request
     * @param $parentKey
     * @return CollectionResource
     */
    public function search(Request $request, $parentKey)
    {
        return $this->index($request, $parentKey);
    }

    /**
     * Create new relation resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Resource
     */
    public function store(Request $request, $parentKey)
    {
        try {
            $this->startTransaction();
            $result = $this->storeWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Create new relation resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Resource
     */
    protected function storeWithTransaction(Request $request, $parentKey)
    {
        $parentQuery = $this->buildStoreParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runStoreParentFetchQuery($request, $parentQuery, $parentKey);

        $resourceModelClass = $this->resolveResourceModelClass();

        $this->authorize($this->resolveAbility('create'), [$resourceModelClass, $parentEntity]);

        /** @var Model $entity */
        $entity = new $resourceModelClass;

        $beforeHookResult = $this->beforeStore($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $beforeSaveHookResult = $this->beforeSave($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        if ($this->isOneToOneRelation($parentEntity)) {
            $query = $this->buildStoreFetchQuery(
                $request, $parentEntity, $requestedRelations
            );

            if ($query->exists()) {
                abort(409, 'Entity already exists.');
            }
        }

        $this->performStore(
            $request,
            $parentEntity,
            $entity,
            $this->retrieve($request),
            $request->get('pivot', [])
        );

        $query = $this->buildStoreFetchQuery($request, $parentEntity, $requestedRelations);

        $entity = $this->runStoreFetchQuery(
            $request,
            $query,
            $parentEntity,
            $entity->{$this->keyName()}
        );
        $entity->wasRecentlyCreated = true;

        $entity = $this->cleanupEntity($entity);

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterSaveHookResult = $this->afterSave($request, $parentEntity, $entity);
        if ($this->hookResponds($afterSaveHookResult)) {
            return $afterSaveHookResult;
        }

        $afterHookResult = $this->afterStore($request, $parentEntity, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in store method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildStoreParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in store method.
     *
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runStoreParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * The hook is executed before creating new relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function beforeStore(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * The hook is executed before creating or updating a relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function beforeSave(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * Fills attributes on the given relation entity and stores it in database.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @param array $attributes
     * @param array $pivot
     */
    protected function performStore(
        Request $request,
        Model $parentEntity,
        Model $entity,
        array $attributes,
        array $pivot
    ): void {
        $this->performFill($request, $parentEntity, $entity, $attributes, $pivot);

        if (!$parentEntity->{$this->getRelation()}() instanceof BelongsTo) {
            $parentEntity->{$this->getRelation()}()->save($entity, $this->preparePivotFields($pivot));
        } else {
            $entity->save();
            $parentEntity->{$this->getRelation()}()->associate($entity);
        }
    }

    /**
     * Builds Eloquent query for fetching relation entity in store method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildStoreFetchQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations);
    }

    /**
     * Runs the given query for fetching relation entity in store method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runStoreFetchQuery(Request $request, Relation $query, Model $parentEntity, $relatedKey): Model
    {
        return $this->runRelationFetchQuery($request, $query, $parentEntity, $relatedKey);
    }

    /**
     * The hook is executed after creating or updating a relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function afterSave(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * The hook is executed after creating new relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function afterStore(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * Fetch a relation resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource
     */
    public function show(Request $request, $parentKey, $relatedKey = null)
    {
        $parentQuery = $this->buildShowParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runShowParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeShow($request, $parentEntity, $relatedKey);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildShowFetchQuery($request, $parentEntity, $requestedRelations);
        $entity = $this->runShowFetchQuery($request, $query, $parentEntity, $relatedKey);

        $this->authorize($this->resolveAbility('show'), [$entity, $parentEntity]);

        $entity = $this->cleanupEntity($entity);

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterHookResult = $this->afterShow($request, $parentEntity, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * The hook is executed before fetching relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param int|string|null $key
     * @return mixed
     */
    protected function beforeShow(Request $request, Model $parentEntity, $key)
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in show method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildShowParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in show method.
     *
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runShowParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in show method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildShowFetchQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations);
    }

    /**
     * Runs the given query for fetching relation entity in show method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runShowFetchQuery(Request $request, Relation $query, Model $parentEntity, $relatedKey): Model
    {
        return $this->runRelationFetchQuery($request, $query, $parentEntity, $relatedKey);
    }

    /**
     * Wrapper function to run the given query for fetching relation entity.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runRelationFetchQuery(Request $request, Relation $query, Model $parentEntity, $relatedKey): Model
    {
        return $this->runRelationFetchQueryBase(
            $request,
            $query,
            $parentEntity,
            $relatedKey
        );
    }

    /**
     * Runs the given query for fetching relation entity.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runRelationFetchQueryBase(
        Request $request,
        Relation $query,
        Model $parentEntity,
        $relatedKey
    ): Model {
        if ($this->isOneToOneRelation($parentEntity)) {
            return $query->firstOrFail();
        }

        $this->abortIfMissingRelatedID($relatedKey);

        return $query->where($this->resolveQualifiedKeyName(), $relatedKey)->firstOrFail();
    }

    /**
     * Determines whether controller relation is one-to-one or not.
     *
     * @param Model $parentEntity
     * @return bool
     */
    protected function isOneToOneRelation(Model $parentEntity)
    {
        $relation = $parentEntity->{$this->getRelation()}();

        return $relation instanceof HasOne || $relation instanceof MorphOne || $relation instanceof BelongsTo || $relation instanceof HasOneThrough;
    }

    /**
     * Throws exception, if related ID is undefined and relation type is not one-to-one.
     *
     * @param int|string|null $relatedKey
     */
    protected function abortIfMissingRelatedID($relatedKey)
    {
        if ($relatedKey) {
            return;
        }
        throw new InvalidArgumentException('Relation key is required, if relation type is not one-to-one');
    }

    /**
     * The hook is executed after fetching a relation resource
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function afterShow(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * Update a relation resource in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource
     */
    public function update(Request $request, $parentKey, $relatedKey = null)
    {
        try {
            $this->startTransaction();
            $result = $this->updateWithTransaction($request, $parentKey, $relatedKey);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Update a relation resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource
     */
    protected function updateWithTransaction(Request $request, $parentKey, $relatedKey = null)
    {
        $parentQuery = $this->buildUpdateParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runUpdateParentFetchQuery($request, $parentQuery, $parentKey);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildUpdateFetchQuery($request, $parentEntity, $requestedRelations);
        $entity = $this->runUpdateFetchQuery($request, $query, $parentEntity, $relatedKey);

        $this->authorize($this->resolveAbility('update'), [$entity, $parentEntity]);

        $beforeHookResult = $this->beforeUpdate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $beforeSaveHookResult = $this->beforeSave($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $this->performUpdate(
            $request,
            $parentEntity,
            $entity,
            $this->retrieve($request),
            $request->get('pivot', [])
        );

        $entity = $this->refreshUpdatedEntity(
            $request, $parentEntity, $requestedRelations,  $relatedKey
        );
        $entity = $this->cleanupEntity($entity);

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterSaveHookResult = $this->afterSave($request, $parentEntity, $entity);
        if ($this->hookResponds($afterSaveHookResult)) {
            return $afterSaveHookResult;
        }

        $afterHookResult = $this->afterUpdate($request, $parentEntity, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in update method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildUpdateParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in update method.
     *
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runUpdateParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildUpdateFetchQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations);
    }

    /**
     * Runs the given query for fetching relation entity in update method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runUpdateFetchQuery(Request $request, Relation $query, Model $parentEntity, $relatedKey): Model
    {
        return $this->runRelationFetchQuery($request, $query, $parentEntity, $relatedKey);
    }

    /**
     * Fetches the relation model that has just been updated using the given key.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @param string|int $relatedKey
     * @return Model
     */
    protected function refreshUpdatedEntity(
        Request $request,
        Model $parentEntity,
        array $requestedRelations,
        $relatedKey
    ): Model {
        $query = $this->buildRelationFetchQueryBase(
            $request,
            $parentEntity,
            $requestedRelations
        );

        return $this->runRelationFetchQueryBase(
            $request,
            $query,
            $parentEntity,
            $relatedKey
        );
    }

    /**
     * The hook is executed before updating a relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function beforeUpdate(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * Fills attributes on the given relation entity and persists changes in database.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @param array $attributes
     * @param array $pivot
     */
    protected function performUpdate(
        Request $request,
        Model $parentEntity,
        Model $entity,
        array $attributes,
        array $pivot
    ): void {
        $this->performFill($request, $parentEntity, $entity, $attributes, $pivot);
        $entity->save();

        $relation = $parentEntity->{$this->getRelation()}();

        if ($relation instanceof BelongsToMany || $relation instanceof MorphToMany) {
            if (count($pivotFields = $this->preparePivotFields($pivot))) {
                $relation->updateExistingPivot($entity->getKey(), $pivotFields);
            }
        }
    }

    /**
     * The hook is executed after updating a relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function afterUpdate(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * Delete a relation resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource
     * @throws Exception
     */
    public function destroy(Request $request, $parentKey, $relatedKey = null)
    {
        try {
            $this->startTransaction();
            $result = $this->destroyWithTransaction($request, $parentKey, $relatedKey);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Delete a relation resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource
     * @throws Exception
     */
    protected function destroyWithTransaction(Request $request, $parentKey, $relatedKey = null)
    {
        $parentQuery = $this->buildDestroyParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runDestroyParentFetchQuery($request, $parentQuery, $parentKey);

        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $forceDeletes = $this->forceDeletes($request, $softDeletes);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildDestroyFetchQuery($request, $parentEntity, $requestedRelations, $softDeletes);
        $entity = $this->runDestroyFetchQuery($request, $query, $parentEntity, $relatedKey);

        if ($this->isResourceTrashed($entity, $softDeletes, $forceDeletes)) {
            abort(404);
        }

        $this->authorize($this->resolveAbility($forceDeletes ? 'forceDelete' : 'delete'), [$entity, $parentEntity]);

        $beforeHookResult = $this->beforeDestroy($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        if (!$forceDeletes) {
            $this->performDestroy($entity);

            if ($softDeletes) {
                $entity = $this->runDestroyFetchQuery($request, $query, $parentEntity, $relatedKey);
            }
        } else {
            $this->performForceDestroy($entity);
        }

        $entity = $this->cleanupEntity($entity);

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterHookResult = $this->afterDestroy($request, $parentEntity, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in destroy method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildDestroyParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in destroy method.
     *
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runDestroyParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in destroy method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @param bool $softDeletes
     * @return Relation
     */
    protected function buildDestroyFetchQuery(
        Request $request,
        Model $parentEntity,
        array $requestedRelations,
        bool $softDeletes
    ): Relation {
        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations)
            ->when(
                $softDeletes,
                function ($query) {
                    $query->withTrashed();
                }
            );
    }

    /**
     * Runs the given query for fetching relation entity in destroy method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runDestroyFetchQuery(Request $request, Relation $query, Model $parentEntity, $relatedKey): Model
    {
        return $this->runRelationFetchQuery($request, $query, $parentEntity, $relatedKey);
    }

    /**
     * The hook is executed before deleting a relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function beforeDestroy(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * Deletes or trashes the given relation entity from database.
     *
     * @param Model $entity
     * @throws Exception
     */
    protected function performDestroy(Model $entity): void
    {
        $entity->delete();
    }

    /**
     * Deletes the given relation entity from database, even if it is soft deletable.
     *
     * @param Model $entity
     */
    protected function performForceDestroy(Model $entity): void
    {
        $entity->forceDelete();
    }

    /**
     * The hook is executed after deleting a relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function afterDestroy(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * Restores a previously deleted relation resource in a transaction-save way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource
     */
    public function restore(Request $request, $parentKey, $relatedKey = null)
    {
        try {
            $this->startTransaction();
            $result = $this->restoreWithTransaction($request, $parentKey, $relatedKey);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Restores a previously deleted relation resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource
     */
    protected function restoreWithTransaction(Request $request, $parentKey, $relatedKey = null)
    {
        $parentQuery = $this->buildRestoreParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runRestoreParentFetchQuery($request, $parentQuery, $parentKey);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildRestoreFetchQuery($request, $parentEntity, $requestedRelations);
        $entity = $this->runRestoreFetchQuery($request, $query, $parentEntity, $relatedKey);

        $this->authorize($this->resolveAbility('restore'), [$entity, $parentEntity]);

        $beforeHookResult = $this->beforeRestore($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->performRestore($entity);

        $entity = $this->runRestoreFetchQuery($request, $query, $parentEntity, $relatedKey);
        $entity = $this->cleanupEntity($entity);

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterHookResult = $this->afterRestore($request, $parentEntity, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in restore method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildRestoreParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in restore method.
     *
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runRestoreParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in restore method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildRestoreFetchQuery(
        Request $request,
        Model $parentEntity,
        array $requestedRelations
    ): Relation {
        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations)
            ->withTrashed();
    }

    /**
     * Runs the given query for fetching relation entity in restore method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runRestoreFetchQuery(Request $request, Relation $query, Model $parentEntity, $relatedKey): Model
    {
        return $this->runRelationFetchQuery($request, $query, $parentEntity, $relatedKey);
    }

    /**
     * The hook is executed before restoring a relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function beforeRestore(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * Restores the given relation entity.
     *
     * @param Model|SoftDeletes $entity
     */
    protected function performRestore(Model $entity): void
    {
        $entity->restore();
    }

    /**
     * The hook is executed after restoring a relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function afterRestore(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * Fills attributes on the given relation entity.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @param array $attributes
     * @param array $pivot
     */
    protected function performFill(
        Request $request,
        Model $parentEntity,
        Model $entity,
        array $attributes,
        array $pivot
    ): void {
        $entity->fill(
            Arr::except($attributes, array_keys($entity->getDirty()))
        );
    }

    /**
     * @param Request $request
     * @param Model $parentEntity
     * @param array $filterDescriptor
     * @return array
     */
    protected function beforeFilterApplied(Request $request, Model $parentEntity, array $filterDescriptor): array
    {
        return $filterDescriptor;
    }
}
