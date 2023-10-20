<?php

declare(strict_types=1);

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\CollectionResource;
use Symfony\Component\HttpFoundation\Response;

trait HandlesStandardBatchOperations
{
    /**
     * Creates a batch of new resources in a transaction-safe way.
     *
     * @param Request $request
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws Exception
     */
    public function batchStore(Request $request): CollectionResource|AnonymousResourceCollection|Response
    {
        try {
            $this->startTransaction();
            $result = $this->runBatchStoreOperation($request);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Creates a batch of new resources.
     *
     * @param Request $request
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws BindingResolutionException
     */
    protected function runBatchStoreOperation(Request $request): CollectionResource|AnonymousResourceCollection|Response
    {
        $beforeHookResult = $this->beforeBatchStore($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceModelClass = $this->resolveResourceModelClass();

        $this->authorize($this->resolveAbility('create'), $resourceModelClass);

        $resources = $this->retrieve($request, 'resources', []);
        $entities = collect([]);

        foreach ($resources as $resource) {
            /**
             * @var Model $entity
             */
            $entity = new $resourceModelClass;

            $this->beforeStore($request, $entity, $resource);
            $this->repositoryInstance->beforeStore($entity, $resource);

            $this->beforeSave($request, $entity, $resource);
            $this->repositoryInstance->beforeSave($entity, $resource);

            $this->performStore($request, $entity, $resource);

            $this->beforeStoreFresh($request, $entity);

            $entityQuery = $this->buildStoreFetchQuery($request);
            $entity = $this->runStoreFetchQuery($request, $entityQuery, $entity->{$this->keyName()});

            $entity->wasRecentlyCreated = true;

            $this->repositoryInstance->afterSave($entity);
            $this->afterSave($request, $entity);

            $this->repositoryInstance->afterStore($entity);
            $this->afterStore($request, $entity);

            $entities->push($entity);
        }

        $afterHookResult = $this->afterBatchStore($request, $entities);
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
     * The hook is executed before creating a batch of new resources.
     *
     * @param Request $request
     * @return Response|null
     */
    protected function beforeBatchStore(Request $request): ?Response
    {
        return null;
    }

    /**
     * The hook is executed after creating a batch of new resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return Response|null
     */
    protected function afterBatchStore(Request $request, Collection $entities): ?Response
    {
        return null;
    }

    /**
     * Update a batch of resources in a transaction-safe way.
     *
     * @param Request $request
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws Exception
     */
    public function batchUpdate(Request $request): CollectionResource|AnonymousResourceCollection|Response
    {
        try {
            $this->startTransaction();
            $result = $this->runBatchUpdateOperation($request);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Update a batch of resources.
     *
     * @param Request $request
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws BindingResolutionException
     */
    protected function runBatchUpdateOperation(Request $request): CollectionResource|AnonymousResourceCollection|Response
    {
        $beforeHookResult = $this->beforeBatchUpdate($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $query = $this->buildBatchUpdateFetchQuery($request);
        $entities = $this->runBatchUpdateFetchQuery($request, $query);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize($this->resolveAbility('update'), $entity);

            $attributes = $this->retrieve($request, "resources.{$entity->{$this->keyName()}}");

            $this->beforeUpdate($request, $entity, $attributes);
            $this->repositoryInstance->beforeUpdate($entity, $attributes);

            $this->beforeSave($request, $entity, $attributes);
            $this->repositoryInstance->beforeSave($entity, $attributes);

            $this->performUpdate($request, $entity, $attributes);

            $this->beforeUpdateFresh($request, $entity);

            $entity = $this->refreshUpdatedEntity(
                $request, $entity->{$this->keyName()}
            );

            $this->repositoryInstance->afterSave($entity);
            $this->afterSave($request, $entity);

            $this->repositoryInstance->afterUpdate($entity);
            $this->afterUpdate($request, $entity);
        }

        $afterHookResult = $this->afterBatchUpdate($request, $entities);
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
     * The hook is executed before updating a batch of resources.
     *
     * @param Request $request
     * @return Response|null
     */
    protected function beforeBatchUpdate(Request $request): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching entities in batch update method.
     *
     * @param Request $request
     * @return Builder
     */
    protected function buildBatchUpdateFetchQuery(Request $request): Builder
    {
        return $this->buildBatchFetchQuery($request);
    }

    /**
     * Builds Eloquent query for fetching entities in batch methods.
     *
     * @param Request $request
     * @return Builder
     */
    protected function buildBatchFetchQuery(Request $request): Builder
    {
        $resourceKeyName = $this->resolveQualifiedKeyName();
        $resourceKeys = $this->resolveResourceKeys($request);

        return $this->buildFetchQuery($request)
            ->whereIn($resourceKeyName, $resourceKeys);
    }

    /**
     * Runs the given query for fetching entities in batch update method.
     *
     * @param Request $request
     * @param Builder $query
     * @return Collection
     */
    protected function runBatchUpdateFetchQuery(Request $request, Builder $query): Collection
    {
        return $this->runBatchFetchQuery($request, $query);
    }

    /**
     * Runs the given query for fetching entities in batch methods.
     *
     * @param Request $request
     * @param Builder $query
     * @return Collection
     */
    protected function runBatchFetchQuery(Request $request, Builder $query): Collection
    {
        return $query->get();
    }

    /**
     * The hook is executed after updating a batch of resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return Response|null
     */
    protected function afterBatchUpdate(Request $request, Collection $entities): ?Response
    {
        return null;
    }

    /**
     * Deletes a batch of resources in a transaction-safe way.
     *
     * @param Request $request
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws Exception
     */
    public function batchDestroy(Request $request): CollectionResource|AnonymousResourceCollection|Response
    {
        try {
            $this->startTransaction();
            $result = $this->runBatchDestroyOperation($request);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Deletes a batch of resources.
     *
     * @param Request $request
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws BindingResolutionException
     */
    protected function runBatchDestroyOperation(Request $request): CollectionResource|AnonymousResourceCollection|Response
    {
        $beforeHookResult = $this->beforeBatchDestroy($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $forceDeletes = $this->forceDeletes($request, $softDeletes);

        $query = $this->buildBatchDestroyFetchQuery($request, $softDeletes);
        $entities = $this->runBatchDestroyFetchQuery($request, $query);

        foreach ($entities as $entity) {
            /**
             * @var Model $entity
             */
            $this->authorize($this->resolveAbility($forceDeletes ? 'forceDelete' : 'delete'), $entity);

            $this->beforeDestroy($request, $entity);
            $this->repositoryInstance->beforeDestroy($entity, $forceDeletes);

            if (!$forceDeletes) {
                $this->performDestroy($entity);

                if ($softDeletes) {
                    $this->beforeDestroyFresh($request, $entity);

                    $entityQuery = $this->buildDestroyFetchQuery($request,  $softDeletes);
                    $entity = $this->runDestroyFetchQuery($request, $entityQuery, $entity->{$this->keyName()});
                }
            } else {
                $this->performForceDestroy($entity);
            }

            $this->repositoryInstance->afterDestroy($entity);
            $this->afterDestroy($request, $entity);
        }

        $afterHookResult = $this->afterBatchDestroy($request, $entities);
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
     * @return Response|null
     */
    protected function beforeBatchDestroy(Request $request): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching entities in batch destroy method.
     *
     * @param Request $request
     * @param bool $softDeletes
     * @return Builder
     */
    protected function buildBatchDestroyFetchQuery(Request $request, bool $softDeletes): Builder {
        return $this->buildBatchFetchQuery($request)
            ->when($softDeletes, function ($query) {
                $query->withTrashed();
            });
    }

    /**
     * Runs the given query for fetching entities in batch destroy method.
     *
     * @param Request $request
     * @param Builder $query
     * @return Collection
     */
    protected function runBatchDestroyFetchQuery(Request $request, Builder $query): Collection
    {
        return $this->runBatchFetchQuery($request, $query);
    }

    /**
     * The hook is executed after deleting a batch of resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return Response|null
     */
    protected function afterBatchDestroy(Request $request, Collection $entities): ?Response
    {
        return null;
    }

    /**
     * Restores a batch of resources in a transaction-safe way.
     *
     * @param Request $request
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws Exception
     */
    public function batchRestore(Request $request): CollectionResource|AnonymousResourceCollection|Response
    {
        try {
            $this->startTransaction();
            $result = $this->runBatchRestoreOperation($request);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Restores a batch of resources.
     *
     * @param Request $request
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws Exception
     */
    protected function runBatchRestoreOperation(Request $request): CollectionResource|AnonymousResourceCollection|Response
    {
        $beforeHookResult = $this->beforeBatchRestore($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $query = $this->buildBatchRestoreFetchQuery($request);
        $entities = $this->runBatchRestoreFetchQuery($request, $query);

        foreach ($entities as $entity) {
            /**
             * @var Model $entity
             */
            $this->authorize($this->resolveAbility('restore'), $entity);

            $this->beforeRestore($request, $entity);
            $this->repositoryInstance->beforeRestore($entity);

            $this->performRestore($entity);

            $this->beforeRestoreFresh($request, $entity);

            $entityQuery = $this->buildRestoreFetchQuery($request);
            $entity = $this->runRestoreFetchQuery($request, $entityQuery, $entity->{$this->keyName()});

            $this->repositoryInstance->afterRestore($entity);
            $this->afterRestore($request, $entity);
        }

        $afterHookResult = $this->afterBatchRestore($request, $entities);
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
     * @return Response|null
     */
    protected function beforeBatchRestore(Request $request): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching entities in batch restore method.
     *
     * @param Request $request
     * @return Builder
     */
    protected function buildBatchRestoreFetchQuery(Request $request): Builder
    {
        return $this->buildBatchFetchQuery($request)->withTrashed();
    }

    /**
     * Runs the given query for fetching entities in batch restore method.
     *
     * @param Request $request
     * @param Builder $query
     * @return Collection
     */
    protected function runBatchRestoreFetchQuery(Request $request, Builder $query): Collection
    {
        return $this->runBatchFetchQuery($request, $query);
    }

    /**
     * The hook is executed after restoring a batch of resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return Response|null
     */
    protected function afterBatchRestore(Request $request, Collection $entities): ?Response
    {
        return null;
    }
}
