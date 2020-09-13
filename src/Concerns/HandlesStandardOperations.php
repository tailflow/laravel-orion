<?php

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\CollectionResource;
use Orion\Http\Resources\Resource;

trait HandlesStandardOperations
{
    /**
     * Fetch the list of resources.
     *
     * @param Request $request
     * @return CollectionResource
     */
    public function index(Request $request)
    {
        $beforeHookResult = $this->beforeIndex($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize('viewAny', $this->resolveResourceModelClass());

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $entities = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($requestedRelations)
            ->paginate($this->paginator->resolvePaginationLimit($request));

        $afterHookResult = $this->afterIndex($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities->getCollection(), $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * Create new resource.
     *
     * @param Request $request
     * @return Resource
     */
    public function store(Request $request)
    {
        $resourceModelClass = $this->resolveResourceModelClass();

        $this->authorize('create', $resourceModelClass);

        /**
         * @var Model $entity
         */
        $entity = new $resourceModelClass;
        $entity->fill($request->only($entity->getFillable()));

        $beforeHookResult = $this->beforeStore($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $entity->save();
        $entity = $entity->fresh($requestedRelations);
        $entity->wasRecentlyCreated = true;

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
     * Fetch resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource
     */
    public function show(Request $request, $key)
    {
        $beforeHookResult = $this->beforeShow($request, $key);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        /**
         * @var Model $entity
         */
        $entity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($requestedRelations)
            ->findOrFail($key);

        $this->authorize('view', $entity);

        $afterHookResult = $this->afterShow($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Update a resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource
     */
    public function update(Request $request, $key)
    {
        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        /**
         * @var Model $entity
         */
        $entity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($requestedRelations)
            ->findOrFail($key);

        $this->authorize('update', $entity);

        $entity->fill($request->only($entity->getFillable()));

        $beforeHookResult = $this->beforeUpdate($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $entity->save();
        $entity = $entity->fresh($requestedRelations);

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
     * Delete a resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource
     * @throws Exception
     */
    public function destroy(Request $request, $key)
    {
        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $entity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($requestedRelations)
            ->when($softDeletes, function ($query) {
                $query->withTrashed();
            })
            ->findOrFail($key);

        $forceDeletes = $softDeletes && $request->get('force');

        if (!$forceDeletes && $softDeletes && $entity->trashed()) {
            abort(404);
        }

        $this->authorize($forceDeletes ? 'forceDelete' : 'delete', $entity);

        $beforeHookResult = $this->beforeDestroy($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        if (!$forceDeletes) {
            $entity->delete();
            if ($softDeletes) {
                $entity = $entity->fresh($requestedRelations);
            }
        } else {
            $entity->forceDelete();
        }

        $afterHookResult = $this->afterDestroy($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Restore previously deleted resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource
     * @throws Exception
     */
    public function restore(Request $request, $key)
    {
        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $entity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($requestedRelations)
            ->withTrashed()
            ->findOrFail($key);

        $this->authorize('restore', $entity);

        $beforeHookResult = $this->beforeRestore($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $entity->restore();
        $entity = $entity->fresh($requestedRelations);

        $afterHookResult = $this->afterRestore($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * The hooks is executed before fetching the list of resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeIndex(Request $request)
    {
        return null;
    }

    /**
     * The hooks is executed after fetching the list of resources.
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
     * The hook is executed before creating new resource.
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
     * The hook is executed after creating new resource.
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
     * The hook is executed before fetching a resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return mixed
     */
    protected function beforeShow(Request $request, $key)
    {
        return null;
    }

    /**
     * The hook is executed after fetching a resource
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
     * The hook is executed before updating a resource.
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
     * The hook is executed after updating a resource.
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
     * The hook is executed before deleting a resource.
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
     * The hook is executed after deleting a resource.
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
     * The hook is executed before force restoring a previously deleted resource.
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
     * The hook is executed after force restoring a previously deleted resource.
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
     * The hook is executed before creating or updating a resource.
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
     * The hook is executed after creating or updating a resource.
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
