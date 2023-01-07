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
     * Create a batch of new relation resources in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     */
    public function batchStore(Request $request, $parentKey)
    {
        try {
            $this->startTransaction();
            $result = $this->batchStoreWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Create a batch of new relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     */
    protected function batchStoreWithTransaction(Request $request, $parentKey)
    {
        $parentQuery = $this->buildBatchStoreParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchStoreParentFetchQuery($request, $parentQuery, $parentKey);

        $resourceModelClass = $this->resolveResourceModelClass();

        $this->authorize($this->resolveAbility('create'), [$resourceModelClass, $parentEntity]);

        $beforeHookResult = $this->beforeBatchStore($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resources = $this->retrieve($request, 'resources', []);
        $entities = collect([]);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        foreach ($resources as $resource) {
            /** @var Model $entity */
            $entity = new $resourceModelClass;

            $this->beforeStore($request, $parentEntity, $entity);
            $this->beforeSave($request, $parentEntity, $entity);

            $this->performStore(
                $request,
                $parentEntity,
                $entity,
                $resource,
                Arr::get($resource, 'pivot', [])
            );

            $entityQuery = $this->buildStoreFetchQuery(
                $request, $parentEntity, $requestedRelations
            );
            $entity = $this->runStoreFetchQuery(
                $request,
                $entityQuery,
                $parentEntity,
                $entity->{$this->keyName()}
            );

            $entity->wasRecentlyCreated = true;

            $entity = $this->cleanupEntity($entity);

            if (count($this->getPivotJson())) {
                $entity = $this->castPivotJsonFields($entity);
            }

            $this->afterSave($request, $parentEntity, $entity);
            $this->afterStore($request, $parentEntity, $entity);

            $entities->push($entity);
        }

        $afterHookResult = $this->afterBatchStore($request, $parentEntity, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before creating a batch of new resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return mixed
     */
    protected function beforeBatchStore(Request $request, Model $parentEntity)
    {
        return null;
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
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runBatchStoreParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * The hook is executed after creating a batch of new resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchStore(Request $request, Model $parentEntity, Collection $entities)
    {
        return null;
    }

    /**
     * Updates a batch of relation resources in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     */
    public function batchUpdate(Request $request, $parentKey)
    {
        try {
            $this->startTransaction();
            $result = $this->batchUpdateWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Updates a batch of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     */
    protected function batchUpdateWithTransaction(Request $request, $parentKey)
    {
        $parentQuery = $this->buildBatchUpdateParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchUpdateParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeBatchUpdate($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchUpdateFetchQuery($request, $parentEntity, $requestedRelations);
        $entities = $this->runBatchUpdateFetchQuery($request, $query, $parentEntity);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize($this->resolveAbility('update'), [$entity, $parentEntity]);

            $resource = $this->retrieve($request, "resources.{$entity->{$this->keyName()}}");

            $this->beforeUpdate($request, $parentEntity, $entity);
            $this->beforeSave($request, $parentEntity, $entity);

            $this->performUpdate(
                $request,
                $parentEntity,
                $entity,
                $resource,
                Arr::get($resource, 'pivot', [])
            );

            $entity = $this->refreshUpdatedEntity(
                $request, $parentEntity,$requestedRelations, $entity->{$this->keyName()}
            );

            $entity = $this->cleanupEntity($entity);

            if (count($this->getPivotJson())) {
                $entity = $this->castPivotJsonFields($entity);
            }

            $this->afterSave($request, $parentEntity, $entity);
            $this->afterUpdate($request, $parentEntity, $entity);
        }

        $afterHookResult = $this->afterBatchUpdate($request, $parentEntity, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before updating a batch of resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return mixed
     */
    protected function beforeBatchUpdate(Request $request, Model $parentEntity)
    {
        return null;
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
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runBatchUpdateParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entities in batch update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildBatchUpdateFetchQuery(
        Request $request,
        Model $parentEntity,
        array $requestedRelations
    ): Relation {
        return $this->buildRelationBatchFetchQuery($request, $parentEntity, $requestedRelations);
    }

    /**
     * Builds Eloquent query for fetching a list of relation entities.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildRelationBatchFetchQuery(
        Request $request,
        Model $parentEntity,
        array $requestedRelations
    ): Relation {
        $resourceKeyName = $this->resolveQualifiedKeyName();
        $resourceKeys = $this->resolveResourceKeys($request);

        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations)
            ->whereIn($resourceKeyName, $resourceKeys);
    }

    /**
     * Runs the given query for fetching relation entities in batch update method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @return Collection
     */
    protected function runBatchUpdateFetchQuery(Request $request, Relation $query, Model $parentEntity): Collection
    {
        return $this->runRelationBatchFetchQuery($request, $query, $parentEntity);
    }

    /**
     * Runs the given query for fetching a list of relation entities.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @return Collection
     */
    protected function runRelationBatchFetchQuery(Request $request, Relation $query, Model $parentEntity): Collection
    {
        return $query->get();
    }

    /**
     * The hook is executed after updating a batch of resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchUpdate(Request $request, Model $parentEntity, Collection $entities)
    {
        return null;
    }

    /**
     * Deletes a batch of relation resources in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     * @throws Exception
     */
    public function batchDestroy(Request $request, $parentKey)
    {
        try {
            $this->startTransaction();
            $result = $this->batchDestroyWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Deletes a batch of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     * @throws Exception
     */
    protected function batchDestroyWithTransaction(Request $request, $parentKey)
    {
        $parentQuery = $this->buildBatchDestroyParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchDestroyParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeBatchDestroy($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $forceDeletes = $this->forceDeletes($request, $softDeletes);
        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchDestroyFetchQuery($request, $parentEntity, $requestedRelations, $softDeletes);
        $entities = $this->runBatchDestroyFetchQuery($request, $query, $parentEntity);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize($this->resolveAbility($forceDeletes ? 'forceDelete' : 'delete'), [$entity, $parentEntity]);

            $this->beforeDestroy($request, $parentEntity, $entity);

            if (!$forceDeletes) {
                $this->performDestroy($entity);

                if ($softDeletes) {
                    $entityQuery = $this->buildDestroyFetchQuery(
                        $request, $parentEntity, $requestedRelations, $softDeletes
                    );
                    $entity = $this->runDestroyFetchQuery(
                        $request, $entityQuery, $parentEntity, $entity->{$this->keyName()}
                    );
                }
            } else {
                $this->performForceDestroy($entity);
            }

            $entity = $this->cleanupEntity($entity);

            if (count($this->getPivotJson())) {
                $entity = $this->castPivotJsonFields($entity);
            }

            $this->afterDestroy($request, $parentEntity, $entity);
        }

        $afterHookResult = $this->afterBatchDestroy($request, $parentEntity, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before deleting a batch of resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return mixed
     */
    protected function beforeBatchDestroy(Request $request, Model $parentEntity)
    {
        return null;
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
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runBatchDestroyParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entities in destroy update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @param bool $softDeletes
     * @return Relation
     */
    protected function buildBatchDestroyFetchQuery(
        Request $request,
        Model $parentEntity,
        array $requestedRelations,
        bool $softDeletes
    ): Relation {
        return $this->buildRelationBatchFetchQuery($request, $parentEntity, $requestedRelations)
            ->when(
                $softDeletes,
                function ($query) {
                    $query->withTrashed();
                }
            );
    }

    /**
     * Runs the given query for fetching relation entities in batch destroy method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @return Collection
     */
    protected function runBatchDestroyFetchQuery(Request $request, Relation $query, Model $parentEntity): Collection
    {
        return $this->runRelationBatchFetchQuery($request, $query, $parentEntity);
    }

    /**
     * The hook is executed after deleting a batch of resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchDestroy(Request $request, Model $parentEntity, Collection $entities)
    {
        return null;
    }

    /**
     * Restores a batch of relation resources in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     * @throws Exception
     */
    public function batchRestore(Request $request, $parentKey)
    {
        try {
            $this->startTransaction();
            $result = $this->batchRestoreWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Restores a batch of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource
     * @throws Exception
     */
    protected function batchRestoreWithTransaction(Request $request, $parentKey)
    {
        $parentQuery = $this->buildBatchRestoreParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchRestoreParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeBatchRestore($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchRestoreFetchQuery($request, $parentEntity, $requestedRelations);
        $entities = $this->runBatchRestoreFetchQuery($request, $query, $parentEntity);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize($this->resolveAbility('restore'), [$entity, $parentEntity]);

            $this->beforeRestore($request, $parentEntity, $entity);

            $this->performRestore($entity);

            $entityQuery = $this->buildRestoreFetchQuery(
                $request, $parentEntity, $requestedRelations
            );
            $entity = $this->runRestoreFetchQuery(
                $request, $entityQuery, $parentEntity, $entity->{$this->keyName()}
            );

            $entity = $this->cleanupEntity($entity);

            if (count($this->getPivotJson())) {
                $entity = $this->castPivotJsonFields($entity);
            }

            $this->afterRestore($request, $parentEntity, $entity);
        }

        $afterHookResult = $this->afterBatchRestore($request, $parentEntity, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before restoring a batch of resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return mixed
     */
    protected function beforeBatchRestore(Request $request, Model $parentEntity)
    {
        return null;
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
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runBatchRestoreParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entities in destroy update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildBatchRestoreFetchQuery(
        Request $request,
        Model $parentEntity,
        array $requestedRelations
    ): Relation {
        return $this->buildRelationBatchFetchQuery($request, $parentEntity, $requestedRelations)
            ->withTrashed();
    }

    /**
     * Runs the given query for fetching relation entities in batch destroy method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @return Collection
     */
    protected function runBatchRestoreFetchQuery(Request $request, Relation $query, Model $parentEntity): Collection
    {
        return $this->runRelationBatchFetchQuery($request, $query, $parentEntity);
    }

    /**
     * The hook is executed after restoring a batch of resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchRestore(Request $request, Model $parentEntity, Collection $entities)
    {
        return null;
    }
}
