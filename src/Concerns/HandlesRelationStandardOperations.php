<?php

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\Resources\Json\ResourceCollection;
use InvalidArgumentException;
use Orion\Http\Requests\Request;

trait HandlesRelationStandardOperations
{
    /**
     * Fetch the list of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return ResourceCollection
     */
    public function index(Request $request, $parentKey)
    {
        $beforeHookResult = $this->beforeIndex($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        if ($this->authorizationRequired()) {
            $this->authorize('viewAny', $this->resolveResourceModelClass());
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $entities = $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($this->relationsResolver->requestedRelations($request))
            ->paginate($this->paginator->resolvePaginationLimit($request));

        if (count($this->getPivotJson())) {
            $entities->getCollection()->transform(function ($entity) {
                return $this->castPivotJsonFields($entity);
            });
        }

        $afterHookResult = $this->afterIndex($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return $this->collectionResponse($entities);
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
        $beforeHookResult = $this->beforeStore($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceModelClass = $this->resolveResourceModelClass();

        if ($this->authorizationRequired()) {
            $this->authorize('create', $resourceModelClass);
        }

        /**
         * @var Model $entity
         */
        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $entity = new $resourceModelClass;
        $entity->fill($request->only($entity->getFillable()));

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        if (!$parentEntity->{$this->getRelation()}() instanceof BelongsTo) {
            $parentEntity->{$this->getRelation()}()->save($entity, $this->preparePivotFields($request->get('pivot', [])));
        } else {
            $entity->save();
            $parentEntity->{$this->getRelation()}()->associate($entity);
        }

        $entity = $entity->fresh($this->relationsResolver->requestedRelations($request));
        $entity->wasRecentlyCreated = true;

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

        return $this->entityResponse($entity);
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

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $query = $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($this->relationsResolver->requestedRelations($request));

        if ($this->isOneToOneRelation($parentEntity)) {
            $entity = $query->firstOrFail();
        } else {
            $this->abortIfMissingRelatedID($relatedKey);
            $entity = $query->findOrFail($relatedKey);
        }

        if ($this->authorizationRequired()) {
            $this->authorize('view', $entity);
        }

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterHookResult = $this->afterShow($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return $this->entityResponse($entity);
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
        $beforeHookResult = $this->beforeUpdate($request, $relatedKey);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $query = $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($this->relationsResolver->requestedRelations($request));

        if ($this->isOneToOneRelation($parentEntity)) {
            $entity = $query->firstOrFail();
        } else {
            $this->abortIfMissingRelatedID($relatedKey);
            $entity = $query->findOrFail($relatedKey);
        }

        if ($this->authorizationRequired()) {
            $this->authorize('update', $entity);
        }

        $entity->fill($request->only($entity->getFillable()));

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $entity->save();

        $relation = $parentEntity->{$this->getRelation()}();
        if ($relation instanceof BelongsToMany || $relation instanceof MorphToMany) {
            $relation->updateExistingPivot($relatedKey, $this->preparePivotFields($request->get('pivot', [])));

            $entity = $entity->fresh($this->relationsResolver->requestedRelations($request));
        }

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

        return $this->entityResponse($entity);
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
        $beforeHookResult = $this->beforeDestroy($request, $relatedKey);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());

        $query = $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($this->relationsResolver->requestedRelations($request))
            ->when($softDeletes, function ($query) {
                $query->withTrashed();
            });

        if ($this->isOneToOneRelation($parentEntity)) {
            $entity = $query->firstOrFail();
        } else {
            $this->abortIfMissingRelatedID($relatedKey);
            $entity = $query->findOrFail($relatedKey);
        }

        $forceDeletes = $softDeletes && $request->get('force');

        if ($this->authorizationRequired()) {
            $this->authorize($forceDeletes ? 'forceDelete' : 'delete', $entity);
        }

        if (!$forceDeletes) {
            $entity->delete();
        } else {
            $entity->forceDelete();
        }

        if (count($this->getPivotJson())) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterHookResult = $this->afterDestroy($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return $this->entityResponse($entity);
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
        $beforeHookResult = $this->beforeRestore($request, $relatedKey);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $relationEntityQuery = $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($this->relationsResolver->requestedRelations($request))
            ->withTrashed();

        if ($this->isOneToOneRelation($parentEntity)) {
            $entity = $relationEntityQuery->firstOrFail();
        } else {
            $this->abortIfMissingRelatedID($relatedKey);
            $entity = $relationEntityQuery->findOrFail($relatedKey);
        }

        if ($this->authorizationRequired()) {
            $this->authorize('restore', $entity);
        }

        $entity->restore();

        $afterHookResult = $this->afterRestore($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return $this->entityResponse($entity);
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
     * @return mixed
     */
    protected function beforeStore(Request $request)
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
     * @param int|string|null $key
     * @return mixed
     */
    protected function beforeUpdate(Request $request, $key)
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
     * @param int|string|null $key
     * @return mixed
     */
    protected function beforeDestroy(Request $request, $key)
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
     * @param int|string|null $key
     * @return mixed
     */
    protected function beforeRestore(Request $request, $key)
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
