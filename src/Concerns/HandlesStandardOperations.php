<?php

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Orion\Http\Resources\Resource;
use Orion\Http\Resources\CollectionResource;
use Orion\Http\Requests\Request;

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

        if ($this->authorizationRequired()) {
            $this->authorize('viewAny', $this->resolveResourceModelClass());
        }

        $entities = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($this->relationsResolver->requestedRelations($request))
            ->paginate($this->paginator->resolvePaginationLimit($request));

        $afterHookResult = $this->afterIndex($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

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
        $entity = new $resourceModelClass;
        $entity->fill($request->only($entity->getFillable()));

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $entity->save();
        $entity = $entity->fresh($this->relationsResolver->requestedRelations($request));
        $entity->wasRecentlyCreated = true;

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

        /**
         * @var Model $entity
         */
        $entity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($this->relationsResolver->requestedRelations($request))
            ->findOrFail($key);

        if ($this->authorizationRequired()) {
            $this->authorize('view', $entity);
        }

        $afterHookResult = $this->afterShow($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

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
        $beforeHookResult = $this->beforeUpdate($request, $key);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        /**
         * @var Model $entity
         */
        $entity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($this->relationsResolver->requestedRelations($request))
            ->findOrFail($key);

        if ($this->authorizationRequired()) {
            $this->authorize('update', $entity);
        }

        $entity->fill($request->only($entity->getFillable()));

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $entity->save();

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
     * Delete a resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource
     * @throws Exception
     */
    public function destroy(Request $request, $key)
    {
        $beforeHookResult = $this->beforeDestroy($request, $key);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());

        $entity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($this->relationsResolver->requestedRelations($request))
            ->when($softDeletes, function ($query) {
                $query->withTrashed();
            })
            ->findOrFail($key);

        $forceDeletes = $softDeletes && $request->get('force');

        if (!$forceDeletes && $softDeletes && $entity->trashed()) {
            abort(404);
        }

        if ($this->authorizationRequired()) {
            $this->authorize($forceDeletes ? 'forceDelete' : 'delete', $entity);
        }

        if (!$forceDeletes) {
            $entity->delete();
        } else {
            $entity->forceDelete();
        }

        $afterHookResult = $this->afterDestroy($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

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
        $beforeHookResult = $this->beforeRestore($request, $key);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $entity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($this->relationsResolver->requestedRelations($request))
            ->withTrashed()
            ->findOrFail($key);

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
     * @return mixed
     */
    protected function beforeStore(Request $request)
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
     * @param int|string $key
     * @return mixed
     */
    protected function beforeUpdate(Request $request, $key)
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
     * @param int|string $key
     * @return mixed
     */
    protected function beforeDestroy(Request $request, $key)
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
     * @param int|string $key
     * @return mixed
     */
    protected function beforeRestore(Request $request, $key)
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
