<?php

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        $this->authorize('viewAny', $this->resolveResourceModelClass());

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $beforeHookResult = $this->beforeIndex($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentQuery = $this->buildIndexParentQuery($request, $parentKey);
        $parentEntity = $this->runIndexParentQuery($parentQuery, $request, $parentKey);

        $query = $this->buildIndexQuery($request, $parentEntity, $requestedRelations);
        $entities = $this->runIndexQuery($query, $request, $parentEntity, $this->paginator->resolvePaginationLimit($request));

        $entities->getCollection()->transform(function ($entity) {
            $entity = $this->cleanupEntity($entity);

            if (count($this->getPivotJson())) {
                $entity = $this->castPivotJsonFields($entity);
            }

            return $entity;
        });

        $afterHookResult = $this->afterIndex($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities->getCollection(), $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * Builds Eloquent query for fetching parent entity in index method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildIndexParentQuery(Request $request, $parentKey): Builder
    {
        return $this->queryBuilder->buildQuery($this->newModelQuery(), $request);
    }

    /**
     * Runs the given query for fetching parent entity in index method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runIndexParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $query->findOrFail($parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entities in index method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildIndexQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        return $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($requestedRelations);
    }

    /**
     * Runs the given query for fetching relation entities in index method.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    protected function runIndexQuery(Relation $query, Request $request, Model $parentEntity, int $paginationLimit): LengthAwarePaginator
    {
        return $query->paginate($paginationLimit);
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
        $resourceModelClass = $this->resolveResourceModelClass();

        $this->authorize('create', $resourceModelClass);

        $parentQuery = $this->buildStoreParentQuery($request, $parentKey);
        $parentEntity = $this->runStoreParentQuery($parentQuery, $request, $parentKey);

        /** @var Model $entity */
        $entity = new $resourceModelClass;

        $beforeHookResult = $this->beforeStore($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $this->performStore($parentEntity, $entity, $request);

        $entity = $this->newRelationQuery($parentEntity)->with($requestedRelations)->find($entity->id);
        $entity->wasRecentlyCreated = true;

        $entity = $this->cleanupEntity($entity);

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterSaveHookResult = $this->afterSave($request, $entity);
        if ($this->hookResponds($afterSaveHookResult)) {
            return $afterSaveHookResult;
        }

        $afterHookResult = $this->afterStore($request, $entity);
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
    protected function buildStoreParentQuery(Request $request, $parentKey): Builder
    {
        return $this->queryBuilder->buildQuery($this->newModelQuery(), $request);
    }

    /**
     * Runs the given query for fetching parent entity in store method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runStoreParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $query->findOrFail($parentKey);
    }

    /**
     * Fills attributes on the given relation entity and stores it in database.
     *
     * @param Model $parentEntity
     * @param Model $entity
     * @param Request $request
     * @return Model
     */
    protected function performStore(Model $parentEntity, Model $entity, Request $request): Model
    {
        $entity->fill($request->only($entity->getFillable()));

        if (!$parentEntity->{$this->getRelation()}() instanceof BelongsTo) {
            return $parentEntity->{$this->getRelation()}()->save($entity, $this->preparePivotFields($request->get('pivot', [])));
        }

        $entity->save(); //TODO: check, if running save here is correct
        $parentEntity->{$this->getRelation()}()->associate($entity);

        return $entity;
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
        $beforeHookResult = $this->beforeShow($request, $relatedKey);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentQuery = $this->buildShowParentQuery($request, $parentKey);
        $parentEntity = $this->runShowParentQuery($parentQuery, $request, $parentKey);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildShowQuery($request, $parentEntity, $requestedRelations);
        $entity = $this->runShowQuery($query, $request, $parentEntity, $relatedKey);

        $this->authorize('view', $entity);

        $entity = $this->cleanupEntity($entity);

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterHookResult = $this->afterShow($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in show method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildShowParentQuery(Request $request, $parentKey): Builder
    {
        return $this->queryBuilder->buildQuery($this->newModelQuery(), $request);
    }

    /**
     * Runs the given query for fetching parent entity in show method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runShowParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $query->findOrFail($parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in show method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildShowQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        return $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($requestedRelations);
    }

    /**
     * Runs the given query for fetching relation entity in show method.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runShowQuery(Relation $query, Request $request, Model $parentEntity, $relatedKey): Model
    {
        if ($this->isOneToOneRelation($parentEntity)) {
            return $query->firstOrFail();
        }

        $this->abortIfMissingRelatedID($relatedKey);

        return $query->findOrFail($relatedKey);
    }

    /**
     * Update a relation resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource
     */
    public function update(Request $request, $parentKey, $relatedKey = null)
    {
        $parentQuery = $this->buildUpdateParentQuery($request, $parentKey);
        $parentEntity = $this->runUpdateParentQuery($parentQuery, $request, $parentKey);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildUpdateQuery($request, $parentEntity, $requestedRelations);
        $entity = $this->runUpdateQuery($query, $request, $parentEntity, $relatedKey);

        $this->authorize('update', $entity);

        $beforeHookResult = $this->beforeUpdate($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $this->performUpdate($parentEntity, $entity, $request);

        $entity = $this->newRelationQuery($parentEntity)->with($requestedRelations)->find($entity->id);

        $entity = $this->cleanupEntity($entity);

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterSaveHookResult = $this->afterSave($request, $entity);
        if ($this->hookResponds($afterSaveHookResult)) {
            return $afterSaveHookResult;
        }

        $afterHookResult = $this->afterUpdate($request, $entity);
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
    protected function buildUpdateParentQuery(Request $request, $parentKey): Builder
    {
        return $this->queryBuilder->buildQuery($this->newModelQuery(), $request);
    }

    /**
     * Runs the given query for fetching parent entity in update method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runUpdateParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $query->findOrFail($parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildUpdateQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        return $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($requestedRelations);
    }

    /**
     * Runs the given query for fetching relation entity in update method.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runUpdateQuery(Relation $query, Request $request, Model $parentEntity, $relatedKey): Model
    {
        if ($this->isOneToOneRelation($parentEntity)) {
            return $query->firstOrFail();
        }

        $this->abortIfMissingRelatedID($relatedKey);

        return $query->findOrFail($relatedKey);
    }

    /**
     * Fills attributes on the given relation entity and persists changes in database.
     *
     * @param Model $parentEntity
     * @param Model $entity
     * @param Request $request
     * @return Model
     */
    protected function performUpdate(Model $parentEntity, Model $entity, Request $request): Model
    {
        $entity->fill($request->only($entity->getFillable()));

        $relation = $parentEntity->{$this->getRelation()}();
        if ($relation instanceof BelongsToMany || $relation instanceof MorphToMany) {
            $relation->updateExistingPivot($entity->getKey(), $this->preparePivotFields($request->get('pivot', [])));
        }

        $entity->save();

        return $entity;
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
        $parentQuery = $this->buildDestroyParentQuery($request, $parentKey);
        $parentEntity = $this->runDestroyParentQuery($parentQuery, $request, $parentKey);

        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $forceDeletes = $softDeletes && $request->get('force');

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildDestroyQuery($request, $parentEntity, $requestedRelations, $softDeletes);
        $entity = $this->runDestroyQuery($query, $request, $parentEntity, $relatedKey);

        $this->authorize($forceDeletes ? 'forceDelete' : 'delete', $entity);

        $beforeHookResult = $this->beforeDestroy($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        if (!$forceDeletes) {
            $this->performDestroy($entity);
            if ($softDeletes) {
                $entity = $this->newRelationQuery($parentEntity)->withTrashed()->with($requestedRelations)->find($entity->id);
            }
        } else {
            $this->performForceDestroy($entity);
        }

        $entity = $this->cleanupEntity($entity);

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterHookResult = $this->afterDestroy($request, $entity);
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
    protected function buildDestroyParentQuery(Request $request, $parentKey): Builder
    {
        return $this->queryBuilder->buildQuery($this->newModelQuery(), $request);
    }

    /**
     * Runs the given query for fetching parent entity in destroy method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runDestroyParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $query->findOrFail($parentKey);
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
    protected function buildDestroyQuery(Request $request, Model $parentEntity, array $requestedRelations, bool $softDeletes): Relation
    {
        return $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($requestedRelations)
            ->when($softDeletes, function ($query) {
                $query->withTrashed();
            });
    }

    /**
     * Runs the given query for fetching relation entity in destroy method.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runDestroyQuery(Relation $query, Request $request, Model $parentEntity, $relatedKey): Model
    {
        if ($this->isOneToOneRelation($parentEntity)) {
            return $query->firstOrFail();
        }

        $this->abortIfMissingRelatedID($relatedKey);

        return $query->findOrFail($relatedKey);
    }

    /**
     * Deletes or trashes the given relation entity from database.
     *
     * @param Model $entity
     * @return Model
     * @throws Exception
     */
    protected function performDestroy(Model $entity): Model
    {
        $entity->delete();

        return $entity;
    }

    /**
     * Deletes the given relation entity from database, even if it is soft deletable.
     *
     * @param Model $entity
     * @return Model
     */
    protected function performForceDestroy(Model $entity): Model
    {
        $entity->forceDelete();

        return $entity;
    }

    /**
     * Restores a previously deleted relation resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource
     */
    public function restore(Request $request, $parentKey, $relatedKey = null)
    {
        $parentQuery = $this->buildRestoreParentQuery($request, $parentKey);
        $parentEntity = $this->runRestoreParentQuery($parentQuery, $request, $parentKey);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildRestoreQuery($request, $parentEntity, $requestedRelations);
        $entity = $this->runRestoreQuery($query, $request, $parentEntity, $relatedKey);

        $this->authorize('restore', $entity);

        $beforeHookResult = $this->beforeRestore($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->performRestore($entity);

        $entity = $this->newRelationQuery($parentEntity)->with($requestedRelations)->find($entity->id);

        $entity = $this->cleanupEntity($entity);

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterHookResult = $this->afterRestore($request, $entity);
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
    protected function buildRestoreParentQuery(Request $request, $parentKey): Builder
    {
        return $this->queryBuilder->buildQuery($this->newModelQuery(), $request);
    }

    /**
     * Runs the given query for fetching parent entity in restore method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runRestoreParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $query->findOrFail($parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in restore method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildRestoreQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        return $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($requestedRelations)
            ->withTrashed();
    }

    /**
     * Runs the given query for fetching relation entity in restore method.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runRestoreQuery(Relation $query, Request $request, Model $parentEntity, $relatedKey): Model
    {
        if ($this->isOneToOneRelation($parentEntity)) {
            return $query->firstOrFail();
        }

        $this->abortIfMissingRelatedID($relatedKey);

        return $query->findOrFail($relatedKey);
    }

    /**
     * Restores the given relation entity.
     *
     * @param Model|SoftDeletes $entity
     * @return Model
     */
    protected function performRestore(Model $entity): Model
    {
        $entity->restore();

        return $entity;
    }

    /**
     * Determines whether controller relation is one-to-one or not.
     *
     * @param Model $resourceEntity
     * @return bool
     */
    protected function isOneToOneRelation($resourceEntity)
    {
        $relation = $resourceEntity->{$this->getRelation()}();
        return $relation instanceof HasOne || $relation instanceof MorphOne || $relation instanceof BelongsTo;
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
     * The hooks is executed before fetching the list of relation resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeIndex(Request $request)
    {
        return null;
    }

    /**
     * The hooks is executed after fetching the list of relation resources.
     *
     * @param Request $request
     * @param Paginator $entities
     * @return mixed
     */
    protected function afterIndex(Request $request, Paginator $entities)
    {
        return null;
    }

    /**
     * The hook is executed before creating new relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function beforeStore(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed after creating new relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterStore(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed before fetching relation resource.
     *
     * @param Request $request
     * @param int|string|null $key
     * @return mixed
     */
    protected function beforeShow(Request $request, $key)
    {
        return null;
    }

    /**
     * The hook is executed after fetching a relation resource
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterShow(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed before updating a relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function beforeUpdate(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed after updating a relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterUpdate(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed before deleting a relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function beforeDestroy(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed after deleting a relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterDestroy(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed before restoring a relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function beforeRestore(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed after restoring a relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterRestore(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed before creating or updating a relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function beforeSave(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed after creating or updating a relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterSave(Request $request, $entity)
    {
        return null;
    }
}
