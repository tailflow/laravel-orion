<?php

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\CollectionResource;
use Orion\Http\Resources\Resource;

trait HandlesStandardOperations
{
    /**
     * Fetches the list of resources.
     *
     * @param Request $request
     * @return CollectionResource
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', $this->resolveResourceModelClass());

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildIndexFetchQuery($request, $requestedRelations);

        $beforeHookResult = $this->beforeIndex($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $entities = $this->runIndexFetchQuery($request, $query, $this->paginator->resolvePaginationLimit($request));

        $afterHookResult = $this->afterIndex($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection(
            $entities instanceof Paginator ? $entities->getCollection() : $entities,
            $requestedRelations
        );

        return $this->collectionResponse($entities);
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return $this->buildFetchQuery($request, $requestedRelations);
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return Paginator|Collection
     */
    protected function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        return $this->shouldPaginate($request, $paginationLimit) ? $query->paginate($paginationLimit) : $query->get();
    }

    /**
     * Creates new resource.
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

        $beforeHookResult = $this->beforeStore($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $this->performStore(
            $request, $entity, $request->all()
        );

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
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill(
            Arr::except($attributes, array_keys($entity->getDirty()))
        );
        $entity->save();
    }

    /**
     * Fetches resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource
     */
    public function show(Request $request, $key)
    {
        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildShowFetchQuery($request, $requestedRelations);

        $beforeHookResult = $this->beforeShow($request, $key);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $entity = $this->runShowFetchQuery($request, $query, $key);

        $this->authorize('view', $entity);

        $afterHookResult = $this->afterShow($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching entity in show method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildShowFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return $this->buildFetchQuery($request, $requestedRelations);
    }

    /**
     * Runs the given query for fetching entity in show method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runShowFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $this->runFetchQuery($request, $query, $key);
    }

    /**
     * Updates a resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource
     */
    public function update(Request $request, $key)
    {
        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildUpdateFetchQuery($request, $requestedRelations);
        $entity = $this->runUpdateFetchQuery($request, $query, $key);

        $this->authorize('update', $entity);

        $beforeHookResult = $this->beforeUpdate($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $this->performUpdate(
            $request, $entity, $request->all()
        );

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
     * Builds Eloquent query for fetching entity in update method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return $this->buildFetchQuery($request, $requestedRelations);
    }

    /**
     * Runs the given query for fetching entity in update method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $this->runFetchQuery($request, $query, $key);
    }

    /**
     * Fills attributes on the given entity and persists changes in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill(
            Arr::except($attributes, array_keys($entity->getDirty()))
        );
        $entity->save();
    }

    /**
     * Deletes a resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource
     * @throws Exception
     */
    public function destroy(Request $request, $key)
    {
        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $forceDeletes = $this->forceDeletes($request, $softDeletes);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildDestroyFetchQuery($request, $requestedRelations, $softDeletes);
        $entity = $this->runDestroyFetchQuery($request, $query, $key);

        if ($this->isResourceTrashed($entity, $softDeletes, $forceDeletes)) {
            abort(404);
        }

        $this->authorize($forceDeletes ? 'forceDelete' : 'delete', $entity);

        $beforeHookResult = $this->beforeDestroy($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        if (!$forceDeletes) {
            $this->performDestroy($entity);
            if ($softDeletes) {
                $entity = $entity->fresh($requestedRelations);
            }
        } else {
            $this->performForceDestroy($entity);
        }

        $afterHookResult = $this->afterDestroy($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching entity in destroy method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @param bool $softDeletes
     * @return Builder
     */
    protected function buildDestroyFetchQuery(Request $request, array $requestedRelations, bool $softDeletes): Builder
    {
        return $this->buildFetchQuery($request, $requestedRelations)
            ->when($softDeletes, function ($query) {
                $query->withTrashed();
            });
    }

    /**
     * Runs the given query for fetching entity in destroy method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runDestroyFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $this->runFetchQuery($request, $query, $key);
    }

    /**
     * Deletes or trashes the given entity from database.
     *
     * @param Model $entity
     * @throws Exception
     */
    protected function performDestroy(Model $entity): void
    {
        $entity->delete();
    }

    /**
     * Deletes the given entity from database, even if it is soft deletable.
     *
     * @param Model $entity
     */
    protected function performForceDestroy(Model $entity): void
    {
        $entity->forceDelete();
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

        $query = $this->buildRestoreFetchQuery($request, $requestedRelations);
        $entity = $this->runRestoreFetchQuery($request, $query, $key);

        $this->authorize('restore', $entity);

        $beforeHookResult = $this->beforeRestore($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->performRestore($entity);

        $entity = $entity->fresh($requestedRelations);

        $afterHookResult = $this->afterRestore($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching entity in restore method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildRestoreFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return $this->buildFetchQuery($request, $requestedRelations)
            ->withTrashed();
    }

    /**
     * Runs the given query for fetching entity in restore method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runRestoreFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $this->runFetchQuery($request, $query, $key);
    }

    /**
     * Restores the given entity.
     *
     * @param Model|SoftDeletes $entity
     */
    protected function performRestore(Model $entity): void
    {
        $entity->restore();
    }

    /**
     * Builds Eloquent query for fetching entity(-ies).
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->with($requestedRelations);
    }

    /**
     * Runs the given query for fetching entity.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->where($this->resolveQualifiedKeyName(), $key)->firstOrFail();
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
     * @param Paginator|Collection $entities
     * @return mixed
     */
    protected function afterIndex(Request $request, $entities)
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
    protected function beforeStore(Request $request, Model $entity)
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
    protected function afterStore(Request $request, Model $entity)
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
    protected function afterShow(Request $request, Model $entity)
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
    protected function beforeUpdate(Request $request, Model $entity)
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
    protected function afterUpdate(Request $request, Model $entity)
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
    protected function beforeDestroy(Request $request, Model $entity)
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
    protected function afterDestroy(Request $request, Model $entity)
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
    protected function beforeRestore(Request $request, Model $entity)
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
    protected function afterRestore(Request $request, Model $entity)
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
    protected function beforeSave(Request $request, Model $entity)
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
    protected function afterSave(Request $request, Model $entity)
    {
        return null;
    }
}
