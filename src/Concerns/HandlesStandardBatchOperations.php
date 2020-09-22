<?php

namespace Orion\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\CollectionResource;

trait HandlesStandardBatchOperations
{
    /**
     * Creates a batch of new resources.
     *
     * @param Request $request
     * @return CollectionResource
     */
    public function batchStore(Request $request)
    {
        $beforeHookResult = $this->beforeBatchStore($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceModelClass = $this->resolveResourceModelClass();

        $this->authorize('create', $resourceModelClass);

        $resources = $request->get('resources', []);
        $entities = collect([]);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        foreach ($resources as $resource) {
            /**
             * @var Model $entity
             */
            $entity = new $resourceModelClass;

            $this->beforeStore($request, $entity);
            $this->beforeSave($request, $entity);

            $this->performStore($entity, $request, Arr::only($resource, $entity->getFillable()));

            $entity = $entity->fresh($requestedRelations);
            $entity->wasRecentlyCreated = true;

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
     * Update a batch of resources.
     *
     * @param Request $request
     * @return CollectionResource
     */
    public function batchUpdate(Request $request)
    {
        $beforeHookResult = $this->beforeBatchUpdate($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchUpdateFetchQuery($request, $requestedRelations);
        $entities = $this->runBatchUpdateFetchQuery($query, $request);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize('update', $entity);

            $this->beforeUpdate($request, $entity);
            $this->beforeSave($request, $entity);

            $this->performUpdate(
                $entity,
                $request,
                Arr::only($request->input("resources.{$entity->getKey()}"),
                    $entity->getFillable())
            );

            $entity = $entity->fresh($requestedRelations);

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
     * Builds Eloquent query for fetching entities in batch update method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildBatchUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return $this->buildBatchFetchQuery($request, $requestedRelations);
    }

    /**
     * Runs the given query for fetching entities in batch update method.
     *
     * @param Builder $query
     * @param Request $request
     * @return Collection
     */
    protected function runBatchUpdateFetchQuery(Builder $query, Request $request): Collection
    {
        return $this->runBatchFetchQuery($query, $request);
    }

    /**
     * Deletes a batch of resources.
     *
     * @param Request $request
     * @return CollectionResource
     * @throws Exception
     */
    public function batchDestroy(Request $request)
    {
        $beforeHookResult = $this->beforeBatchDestroy($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $forceDeletes = $this->forceDeletes($request, $softDeletes);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchDestroyFetchQuery($request, $requestedRelations);
        $entities = $this->runBatchDestroyFetchQuery($query, $request);

        foreach ($entities as $entity) {
            /**
             * @var Model $entity
             */
            $this->authorize($forceDeletes ? 'forceDelete' : 'delete', $entity);

            $this->beforeDestroy($request, $entity);

            if (!$forceDeletes) {
                $this->performDestroy($entity);
                if ($softDeletes) {
                    $entity = $entity->fresh($requestedRelations);
                }
            } else {
                $this->performForceDestroy($entity);
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
     * Builds Eloquent query for fetching entities in batch destroy method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildBatchDestroyFetchQuery(Request $request, array $requestedRelations): Builder
    {
       return $this->buildBatchFetchQuery($request, $requestedRelations);
    }

    /**
     * Runs the given query for fetching entities in batch destroy method.
     *
     * @param Builder $query
     * @param Request $request
     * @return Collection
     */
    protected function runBatchDestroyFetchQuery(Builder $query, Request $request): Collection
    {
        return $this->runBatchFetchQuery($query, $request);
    }

    /**
     * Restores a batch of resources.
     *
     * @param Request $request
     * @return CollectionResource
     * @throws Exception
     */
    public function batchRestore(Request $request)
    {
        $beforeHookResult = $this->beforeBatchRestore($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchUpdateFetchQuery($request, $requestedRelations);
        $entities = $this->runBatchUpdateFetchQuery($query, $request);

        foreach ($entities as $entity) {
            /**
             * @var Model $entity
             */
            $this->authorize('restore', $entity);

            $this->beforeRestore($request, $entity);

            $this->performRestore($entity);

            $entity = $entity->fresh($requestedRelations);

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
     * Builds Eloquent query for fetching entities in batch restore method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildBatchRestoreFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return $this->buildBatchFetchQuery($request, $requestedRelations)->withTrashed();
    }

    /**
     * Runs the given query for fetching entities in batch restore method.
     *
     * @param Builder $query
     * @param Request $request
     * @return Collection
     */
    protected function runBatchRestoreFetchQuery(Builder $query, Request $request): Collection
    {
        return $this->runBatchFetchQuery($query, $request);
    }

    /**
     * Builds Eloquent query for fetching entities in batch methods.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildBatchFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $resourceModelClass = $this->resolveResourceModelClass();
        $resourceKeyName = (new $resourceModelClass)->getKeyName();

        return $this->buildFetchQuery($request, $requestedRelations)
            ->whereIn($resourceKeyName, array_keys($request->get('resources', [])));
    }

    /**
     * Runs the given query for fetching entities in batch methods.
     *
     * @param Builder $query
     * @param Request $request
     * @return Collection
     */
    protected function runBatchFetchQuery(Builder $query, Request $request): Collection
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