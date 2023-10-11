<?php

declare(strict_types=1);

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\CollectionResource;
use Symfony\Component\HttpFoundation\Response;

trait HandlesRelationStandardBatchOperations
{
    /**
     * Create a batch of new relation resources in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws Exception
     */
    public function batchStore(Request $request, int|string $parentKey): CollectionResource|AnonymousResourceCollection|Response
    {
        try {
            $this->startTransaction();
            $result = $this->batchStoreWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Create a batch of new relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws BindingResolutionException
     */
    protected function batchStoreWithTransaction(Request $request, int|string $parentKey): CollectionResource|AnonymousResourceCollection|Response
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

            $entityQuery = $this->buildStoreFetchQuery($request, $parentEntity);
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

        $entities = $this->getAppendsResolver()->appendToCollection($entities, $request);

        $this->relationsResolver->guardRelationsForCollection(
            $entities,
            $this->relationsResolver->requestedRelations($request)
        );

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before creating a batch of new resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Response|null
     */
    protected function beforeBatchStore(Request $request, Model $parentEntity): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in batch store method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildBatchStoreParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in batch store method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runBatchStoreParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * The hook is executed after creating a batch of new resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Collection $entities
     * @return Response|null
     */
    protected function afterBatchStore(Request $request, Model $parentEntity, Collection $entities): ?Response
    {
        return null;
    }

    /**
     * Updates a batch of relation resources in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResCollectionResource|AnonymousResourceCollection|Responseource
     * @throws Exception
     */
    public function batchUpdate(Request $request, int|string $parentKey): CollectionResource|AnonymousResourceCollection|Response
    {
        try {
            $this->startTransaction();
            $result = $this->batchUpdateWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Updates a batch of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws BindingResolutionException
     */
    protected function batchUpdateWithTransaction(Request $request, int|string $parentKey): CollectionResource|AnonymousResourceCollection|Response
    {
        $parentQuery = $this->buildBatchUpdateParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchUpdateParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeBatchUpdate($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $query = $this->buildBatchUpdateFetchQuery($request, $parentEntity);
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
                $request,
                $parentEntity,
                $entity->{$this->keyName()}
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

        $entities = $this->getAppendsResolver()->appendToCollection($entities, $request);

        $this->relationsResolver->guardRelationsForCollection(
            $entities,
            $this->relationsResolver->requestedRelations($request)
        );

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before updating a batch of resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Response|null
     */
    protected function beforeBatchUpdate(Request $request, Model $parentEntity): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in batch update method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildBatchUpdateParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in batch update method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runBatchUpdateParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entities in batch update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Relation
     */
    protected function buildBatchUpdateFetchQuery(
        Request $request,
        Model $parentEntity
    ): Relation {
        return $this->buildRelationBatchFetchQuery($request, $parentEntity);
    }

    /**
     * Builds Eloquent query for fetching a list of relation entities.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Relation
     */
    protected function buildRelationBatchFetchQuery(
        Request $request,
        Model $parentEntity
    ): Relation {
        $resourceKeyName = $this->resolveQualifiedKeyName();
        $resourceKeys = $this->resolveResourceKeys($request);

        return $this->buildRelationFetchQuery($request, $parentEntity)
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
     * @return Response|null
     */
    protected function afterBatchUpdate(Request $request, Model $parentEntity, Collection $entities): ?Response
    {
        return null;
    }

    /**
     * Deletes a batch of relation resources in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws Exception
     */
    public function batchDestroy(Request $request, int|string $parentKey): CollectionResource|AnonymousResourceCollection|Response
    {
        try {
            $this->startTransaction();
            $result = $this->batchDestroyWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Deletes a batch of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws Exception
     */
    protected function batchDestroyWithTransaction(Request $request, int|string $parentKey): CollectionResource|AnonymousResourceCollection|Response
    {
        $parentQuery = $this->buildBatchDestroyParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchDestroyParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeBatchDestroy($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $forceDeletes = $this->forceDeletes($request, $softDeletes);

        $query = $this->buildBatchDestroyFetchQuery($request, $parentEntity, $softDeletes);
        $entities = $this->runBatchDestroyFetchQuery($request, $query, $parentEntity);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize($this->resolveAbility($forceDeletes ? 'forceDelete' : 'delete'), [$entity, $parentEntity]);

            $this->beforeDestroy($request, $parentEntity, $entity);

            if (!$forceDeletes) {
                $this->performDestroy($entity);

                if ($softDeletes) {
                    $entityQuery = $this->buildDestroyFetchQuery($request, $parentEntity, $softDeletes);
                    $entity = $this->runDestroyFetchQuery(
                        $request,
                        $entityQuery,
                        $parentEntity,
                        $entity->{$this->keyName()}
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

        $entities = $this->getAppendsResolver()->appendToCollection($entities, $request);

        $this->relationsResolver->guardRelationsForCollection(
            $entities, $this->relationsResolver->requestedRelations($request)
        );

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before deleting a batch of resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Response|null
     */
    protected function beforeBatchDestroy(Request $request, Model $parentEntity): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in batch destroy method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildBatchDestroyParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in batch destroy method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runBatchDestroyParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entities in destroy update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param bool $softDeletes
     * @return Relation
     */
    protected function buildBatchDestroyFetchQuery(
        Request $request,
        Model $parentEntity,
        bool $softDeletes
    ): Relation {
        return $this->buildRelationBatchFetchQuery($request, $parentEntity)
            ->when($softDeletes, function ($query) {
                $query->withTrashed();
            });
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
     * @return Response|null
     */
    protected function afterBatchDestroy(Request $request, Model $parentEntity, Collection $entities): ?Response
    {
        return null;
    }

    /**
     * Restores a batch of relation resources in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws Exception
     */
    public function batchRestore(Request $request, int|string $parentKey): CollectionResource|AnonymousResourceCollection|Response
    {
        try {
            $this->startTransaction();
            $result = $this->batchRestoreWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Restores a batch of relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws Exception
     */
    protected function batchRestoreWithTransaction(Request $request, int|string $parentKey): CollectionResource|AnonymousResourceCollection|Response
    {
        $parentQuery = $this->buildBatchRestoreParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runBatchRestoreParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeBatchRestore($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $query = $this->buildBatchRestoreFetchQuery($request, $parentEntity);
        $entities = $this->runBatchRestoreFetchQuery($request, $query, $parentEntity);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize($this->resolveAbility('restore'), [$entity, $parentEntity]);

            $this->beforeRestore($request, $parentEntity, $entity);

            $this->performRestore($entity);

            $entityQuery = $this->buildRestoreFetchQuery($request, $parentEntity);
            $entity = $this->runRestoreFetchQuery(
                $request,
                $entityQuery,
                $parentEntity,
                $entity->{$this->keyName()}
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

        $entities = $this->getAppendsResolver()->appendToCollection($entities, $request);

        $this->relationsResolver->guardRelationsForCollection(
            $entities, $this->relationsResolver->requestedRelations($request)
        );

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before restoring a batch of resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Response|null
     */
    protected function beforeBatchRestore(Request $request, Model $parentEntity): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in batch restore method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildBatchRestoreParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in batch restore method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runBatchRestoreParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entities in destroy update method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Relation
     */
    protected function buildBatchRestoreFetchQuery(
        Request $request,
        Model $parentEntity
    ): Relation {
        return $this->buildRelationBatchFetchQuery($request, $parentEntity)
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
     * @return Response|null
     */
    protected function afterBatchRestore(Request $request, Model $parentEntity, Collection $entities): ?Response
    {
        return null;
    }
}
