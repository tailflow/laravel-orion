<?php

namespace Orion\Concerns;

use Exception;
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
            $entity->fill(Arr::only($resource, $entity->getFillable()));

            $this->beforeStore($request, $entity);
            $this->beforeSave($request, $entity);

            $entity->save();
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

        $resourceModelClass = $this->resolveResourceModelClass();
        $resourceKeyName = (new $resourceModelClass)->getKeyName();

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $entities = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($requestedRelations)
            ->whereIn($resourceKeyName, array_keys($request->get('resources', [])))
            ->get();

        foreach ($entities as $entity) {
            /**
             * @var Model $entity
             */
            $this->authorize('update', $entity);

            $entity->fill(Arr::only($request->input("resources.{$entity->getKey()}"), $entity->getFillable()));

            $this->beforeUpdate($request, $entity);
            $this->beforeSave($request, $entity);

            $entity->save();

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

        $resourceModelClass = $this->resolveResourceModelClass();
        $resourceKeyName = (new $resourceModelClass)->getKeyName();

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $entities = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($requestedRelations)
            ->whereIn($resourceKeyName, $request->get('resources', []))
            ->get();

        $forceDeletes = $softDeletes && $request->get('force');

        foreach ($entities as $entity) {
            /**
             * @var Model $entity
             */
            $this->authorize($forceDeletes ? 'forceDelete' : 'delete', $entity);

            $this->beforeDestroy($request, $entity);

            if (!$forceDeletes) {
                $entity->delete();
            } else {
                $entity->forceDelete();
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

        $resourceModelClass = $this->resolveResourceModelClass();
        $resourceKeyName = (new $resourceModelClass)->getKeyName();

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $entities = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($requestedRelations)
            ->whereIn($resourceKeyName, $request->get('resources', []))
            ->withTrashed()
            ->get();

        foreach ($entities as $entity) {
            /**
             * @var Model $entity
             */
            $this->authorize('restore', $entity);

            $this->beforeRestore($request, $entity);

            $entity->restore();

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