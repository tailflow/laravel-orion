<?php

namespace Orion\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\CollectionResource;

trait HandlesRelationStandardBatchOperations
{
    /**
     * Create a batch of new relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     */
    public function batchStore(Request $request, $parentKey)
    {
        $beforeHookResult = $this->beforeBatchStore($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceModelClass = $this->resolveResourceModelClass();

        $this->authorize('create', $resourceModelClass);

        $parentQuery = $this->buildBatchStoreParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchStoreParentFetchQuery($parentQuery, $request, $parentKey);

        $resources = $request->get('resources', []);
        $entities = collect([]);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        foreach ($resources as $resource) {
            /** @var Model $entity */
            $entity = new $resourceModelClass;

            $this->beforeStore($request, $entity);
            $this->beforeSave($request, $entity);

            $this->performStore(
                $parentEntity,
                $entity,
                $request,
                Arr::only($resource, $entity->getFillable()),
                Arr::get($resource, 'pivot', [])
            );

            $entity = $this->newRelationQuery($parentEntity)->with($requestedRelations)->find($entity->id);
            $entity->wasRecentlyCreated = true;

            $entity = $this->cleanupEntity($entity);

            if (count($this->getPivotJson())) {
                $entity = $this->castPivotJsonFields($entity);
            }

            $this->afterSave($request, $entity);
            $this->afterStore($request, $entity);

            $entities->push($entity);
        }

        $afterHookResult = $this->afterBatchStore($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * Builds Eloquent query for fetching parent entity in batch store method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildBatchStoreParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in batch store method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runBatchStoreParentFetchQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Updates a batch of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     */
    public function batchUpdate(Request $request, $parentKey)
    {
        $beforeHookResult = $this->beforeBatchUpdate($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentQuery = $this->buildBatchUpdateParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchUpdateParentFetchQuery($parentQuery, $request, $parentKey);

        $resourceModelClass = $this->resolveResourceModelClass();
        $resourceKeyName = (new $resourceModelClass)->getKeyName();

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchUpdateFetchQuery($request, $parentEntity, $requestedRelations, $resourceKeyName);
        $entities = $this->runBatchUpdateFetchQuery($query, $request, $parentEntity);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize('update', $entity);

            $resource = $request->input("resources.{$entity->getKey()}");

            $this->beforeUpdate($request, $entity);
            $this->beforeSave($request, $entity);

            $this->performUpdate(
                $parentEntity,
                $entity,
                $request,
                Arr::only($resource, $entity->getFillable()),
                Arr::get($resource, 'pivot', [])
            );

            $entity = $this->newRelationQuery($parentEntity)->with($requestedRelations)->find($entity->id);

            $entity = $this->cleanupEntity($entity);

            if (count($this->getPivotJson())) {
                $entity = $this->castPivotJsonFields($entity);
            }

            $this->afterSave($request, $entity);
            $this->afterUpdate($request, $entity);
        }

        $afterHookResult = $this->afterBatchUpdate($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * Builds Eloquent query for fetching parent entity in batch update method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildBatchUpdateParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in batch update method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runBatchUpdateParentFetchQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entities in batch update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @param string $resourceKeyName
     * @return Relation
     */
    protected function buildBatchUpdateFetchQuery(Request $request, Model $parentEntity, array $requestedRelations, string $resourceKeyName): Relation
    {
        return $this->buildRelationBatchFetchQuery($request, $parentEntity, $requestedRelations, $resourceKeyName);
    }

    /**
     * Runs the given query for fetching relation entities in batch update method.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @return Collection
     */
    protected function runBatchUpdateFetchQuery(Relation $query, Request $request, Model $parentEntity): Collection
    {
        return $this->runRelationBatchFetchQuery($query, $request, $parentEntity);
    }

    /**
     * Deletes a batch of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     * @throws Exception
     */
    public function batchDestroy(Request $request, $parentKey)
    {
        $beforeHookResult = $this->beforeBatchDestroy($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentQuery = $this->buildBatchDestroyParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchDestroyParentFetchQuery($parentQuery, $request, $parentKey);

        $resourceModelClass = $this->resolveResourceModelClass();
        $resourceKeyName = (new $resourceModelClass)->getKeyName();

        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $forceDeletes = $this->forceDeletes($request, $softDeletes);
        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchDestroyFetchQuery($request, $parentEntity, $requestedRelations, $resourceKeyName, $softDeletes);
        $entities = $this->runBatchDestroyFetchQuery($query, $request, $parentEntity);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize($forceDeletes ? 'forceDelete' : 'delete', $entity);

            $this->beforeDestroy($request, $entity);

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

            $this->afterDestroy($request, $entity);
        }

        $afterHookResult = $this->afterBatchDestroy($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * Builds Eloquent query for fetching parent entity in batch destroy method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildBatchDestroyParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in batch destroy method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runBatchDestroyParentFetchQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entities in destroy update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @param string $resourceKeyName
     * @param bool $softDeletes
     * @return Relation
     */
    protected function buildBatchDestroyFetchQuery(Request $request, Model $parentEntity, array $requestedRelations, string $resourceKeyName, bool $softDeletes): Relation
    {
        return $this->buildRelationBatchFetchQuery($request, $parentEntity, $requestedRelations, $resourceKeyName)
            ->when($softDeletes, function ($query) {
                $query->withTrashed();
            });
    }

    /**
     * Runs the given query for fetching relation entities in batch destroy method.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @return Collection
     */
    protected function runBatchDestroyFetchQuery(Relation $query, Request $request, Model $parentEntity): Collection
    {
        return $this->runRelationBatchFetchQuery($query, $request, $parentEntity);
    }

    /**
     * Restores a batch of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     * @throws Exception
     */
    public function batchRestore(Request $request, $parentKey)
    {
        $beforeHookResult = $this->beforeBatchRestore($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentQuery = $this->buildBatchRestoreParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchRestoreParentFetchQuery($parentQuery, $request, $parentKey);

        $resourceModelClass = $this->resolveResourceModelClass();
        $resourceKeyName = (new $resourceModelClass)->getKeyName();

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchRestoreFetchQuery($request, $parentEntity, $requestedRelations, $resourceKeyName);
        $entities = $this->runBatchRestoreFetchQuery($query, $request, $parentEntity);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize('restore', $entity);

            $this->beforeRestore($request, $entity);

            $this->performRestore($entity);

            $entity = $this->newRelationQuery($parentEntity)->with($requestedRelations)->find($entity->id);

            $entity = $this->cleanupEntity($entity);

            if (count($this->getPivotJson())) {
                $entity = $this->castPivotJsonFields($entity);
            }

            $this->afterRestore($request, $entity);
        }

        $afterHookResult = $this->afterBatchRestore($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * Builds Eloquent query for fetching parent entity in batch restore method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildBatchRestoreParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in batch restore method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runBatchRestoreParentFetchQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entities in destroy update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @param string $resourceKeyName
     * @return Relation
     */
    protected function buildBatchRestoreFetchQuery(Request $request, Model $parentEntity, array $requestedRelations, string $resourceKeyName): Relation
    {
        return $this->buildRelationBatchFetchQuery($request, $parentEntity, $requestedRelations, $resourceKeyName)
            ->withTrashed();
    }

    /**
     * Runs the given query for fetching relation entities in batch destroy method.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @return Collection
     */
    protected function runBatchRestoreFetchQuery(Relation $query, Request $request, Model $parentEntity): Collection
    {
        return $this->runRelationBatchFetchQuery($query, $request, $parentEntity);
    }

    /**
     * Builds Eloquent query for fetching a list of relation entities.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @param string $resourceKeyName
     * @return Relation
     */
    protected function buildRelationBatchFetchQuery(Request $request, Model $parentEntity, array $requestedRelations, string $resourceKeyName): Relation
    {
        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations)
            ->whereIn($resourceKeyName, array_keys($request->get('resources', [])));
    }

    /**
     * Runs the given query for fetching a list of relation entities.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @return Collection
     */
    protected function runRelationBatchFetchQuery(Relation $query, Request $request, Model $parentEntity): Collection
    {
        return $query->get();
    }

    /**
     * The hook is executed before creating a batch of new resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeBatchStore(Request $request)
    {
        return null;
    }

    /**
     * The hook is executed after creating a batch of new resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchStore(Request $request, Collection $entities)
    {
        return null;
    }

    /**
     * The hook is executed before updating a batch of resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeBatchUpdate(Request $request)
    {
        return null;
    }

    /**
     * The hook is executed after updating a batch of resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchUpdate(Request $request, Collection $entities)
    {
        return null;
    }

    /**
     * The hook is executed before deleting a batch of resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeBatchDestroy(Request $request)
    {
        return null;
    }

    /**
     * The hook is executed after deleting a batch of resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchDestroy(Request $request, Collection $entities)
    {
        return null;
    }

    /**
     * The hook is executed before restoring a batch of resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeBatchRestore(Request $request)
    {
        return null;
    }

    /**
     * The hook is executed after restoring a batch of resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchRestore(Request $request, Collection $entities)
    {
        return null;
    }
}