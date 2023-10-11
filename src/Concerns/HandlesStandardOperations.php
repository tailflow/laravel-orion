<?php

declare(strict_types=1);

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\CollectionResource;
use Orion\Http\Resources\Resource;
use Symfony\Component\HttpFoundation\Response;

trait HandlesStandardOperations
{
    /**
     * Fetches the list of resources.
     *
     * @param Request $request
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws BindingResolutionException
     */
    public function index(Request $request): CollectionResource|AnonymousResourceCollection|Response
    {
        $this->authorize($this->resolveAbility('index'), $this->resolveResourceModelClass());

        $query = $this->buildIndexFetchQuery($request);

        $beforeHookResult = $this->beforeIndex($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $entities = $this->runIndexFetchQuery($request, $query, $this->paginator->resolvePaginationLimit($request));

        $afterHookResult = $this->afterIndex($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entities = $this->getAppendsResolver()->appendToCollection($entities, $request);

        $this->relationsResolver->guardRelationsForCollection(
            $entities instanceof Paginator ? $entities->getCollection() : $entities,
            $this->relationsResolver->requestedRelations($request)
        );

        return $this->collectionResponse($entities);
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @return Builder
     */
    protected function buildIndexFetchQuery(Request $request): Builder
    {
        $filters = collect($request->get('filters', []))
            ->map(function (array $filterDescriptor) use ($request) {
                return $this->beforeFilterApplied($request, $filterDescriptor);
            })->toArray();

        $request->request->add(['filters' => $filters]);

        return $this->buildFetchQuery($request);
    }

    /**
     * Wrapper function to build Eloquent query for fetching entity(-ies).
     *
     * @param Request $request
     * @return Builder
     */
    protected function buildFetchQuery(Request $request): Builder
    {
        return $this->buildFetchQueryBase($request);
    }

    /**
     * Builds Eloquent query for fetching entity(-ies).
     *
     * @param Request $request
     * @return Builder
     */
    protected function buildFetchQueryBase(Request $request): Builder
    {
        return $this->queryBuilder->buildQuery($this->newModelQuery(), $request);
    }

    /**
     * The hooks is executed before fetching the list of resources.
     *
     * @param Request $request
     * @return Response|null
     */
    protected function beforeIndex(Request $request): ?Response
    {
        return null;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return Paginator|Collection
     * @throws BindingResolutionException
     */
    protected function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit): Paginator|Collection
    {
        return $this->shouldPaginate($request, $paginationLimit) ? $query->paginate($paginationLimit) : $query->get();
    }

    /**
     * The hook is executed after fetching the list of resources.
     *
     * @param Request $request
     * @param Paginator|Collection $entities
     * @return Response|null
     */
    protected function afterIndex(Request $request, Paginator|Collection $entities): ?Response
    {
        return null;
    }

    /**
     * Filters, sorts, and fetches the list of resources.
     *
     * @param Request $request
     * @return CollectionResource|AnonymousResourceCollection|Response
     * @throws BindingResolutionException
     */
    public function search(Request $request): CollectionResource|AnonymousResourceCollection|Response
    {
        return $this->index($request);
    }

    /**
     * Creates new resource in a transaction-safe way.
     *
     * @param Request $request
     * @return Resource|Response
     * @throws Exception
     */
    public function store(Request $request): Resource|Response
    {
        try {
            $this->startTransaction();
            $result = $this->storeWithTransaction($request);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Creates new resource.
     *
     * @param Request $request
     * @return Resource|Response
     * @throws BindingResolutionException
     */
    protected function storeWithTransaction(Request $request): Resource|Response
    {
        $resourceModelClass = $this->resolveResourceModelClass();

        $this->authorize($this->resolveAbility('create'), $resourceModelClass);

        $entity = $this->repositoryInstance->make();
        $attributes = $this->retrieve($request);

        $beforeHookResult = $this->beforeStore($request, $entity, $attributes);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->repositoryInstance->beforeStore($entity, $attributes);

        $beforeSaveHookResult = $this->beforeSave($request, $entity, $attributes);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $this->repositoryInstance->beforeSave($entity, $attributes);

        $this->performStore($request, $entity, $attributes);

        $beforeStoreFreshResult = $this->beforeStoreFresh($request, $entity);
        if ($this->hookResponds($beforeStoreFreshResult)) {
            return $beforeStoreFreshResult;
        }

        $query = $this->buildStoreFetchQuery($request);

        $entity = $this->runStoreFetchQuery($request, $query, $entity->{$this->keyName()});
        $entity->wasRecentlyCreated = true;

        $this->repositoryInstance->afterSave($entity);

        $afterSaveHookResult = $this->afterSave($request, $entity);
        if ($this->hookResponds($afterSaveHookResult)) {
            return $afterSaveHookResult;
        }

        $this->repositoryInstance->afterStore($entity);

        $afterHookResult = $this->afterStore($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->getAppendsResolver()->appendToEntity($entity, $request);
        $entity = $this->getRelationsResolver()->guardRelations(
            $entity,
            $this->getRelationsResolver()->requestedRelations($request)
        );

        return $this->entityResponse($entity);
    }

    /**
     * The hook is executed before creating new resource.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     * @return Response|null
     */
    protected function beforeStore(Request $request, Model $entity, array &$attributes): ?Response
    {
        return null;
    }

    /**
     * The hook is executed before creating or updating a resource.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     * @return Response|null
     */
    protected function beforeSave(Request $request, Model $entity, array &$attributes): ?Response
    {
        return null;
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
        $this->repositoryInstance->performFill($entity, $attributes);
        $this->repositoryInstance->performStore($entity);
    }

    /**
     * Builds Eloquent query for fetching entity in store method.
     *
     * @param Request $request
     * @return Builder
     */
    protected function buildStoreFetchQuery(Request $request): Builder
    {
        return $this->buildFetchQuery($request);
    }

    /**
     * Runs the given query for fetching entity in store method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runStoreFetchQuery(Request $request, Builder $query, int|string $key): Model
    {
        return $this->runFetchQuery($request, $query, $key);
    }

    /**
     * The hook is executed after creating and before refreshing the resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function beforeStoreFresh(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * The hook is executed after creating or updating a resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function afterSave(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * The hook is executed after creating new resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function afterStore(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * Fetches resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource|Response
     * @throws BindingResolutionException
     */
    public function show(Request $request, int|string $key): Resource|Response
    {
        $query = $this->buildShowFetchQuery($request);

        $beforeHookResult = $this->beforeShow($request, $key);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $entity = $this->runShowFetchQuery($request, $query, $key);

        $this->authorize($this->resolveAbility('show'), $entity);

        $afterHookResult = $this->afterShow($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->getAppendsResolver()->appendToEntity($entity, $request);
        $entity = $this->getRelationsResolver()->guardRelations(
            $entity,
            $this->relationsResolver->requestedRelations($request)
        );

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching entity in show method.
     *
     * @param Request $request
     * @return Builder
     */
    protected function buildShowFetchQuery(Request $request): Builder
    {
        return $this->buildFetchQuery($request);
    }

    /**
     * The hook is executed before fetching a resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Response|null
     */
    protected function beforeShow(Request $request, int|string $key): ?Response
    {
        return null;
    }

    /**
     * Runs the given query for fetching entity in show method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runShowFetchQuery(Request $request, Builder $query, int|string $key): Model
    {
        return $this->runFetchQuery($request, $query, $key);
    }

    /**
     * Wrapper function to run the given query for fetching entity.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runFetchQuery(Request $request, Builder $query, int|string $key): Model
    {
        return $this->runFetchQueryBase($request, $query, $key);
    }

    /**
     * Runs the given query for fetching entity.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runFetchQueryBase(Request $request, Builder $query, int|string $key): Model
    {
        return $query->where($this->resolveQualifiedKeyName(), $key)->firstOrFail();
    }

    /**
     * The hook is executed after fetching a resource
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function afterShow(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * Update a resource in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource|Response
     * @throws Exception
     */
    public function update(Request $request, int|string $key): Resource|Response
    {
        try {
            $this->startTransaction();
            $result = $this->updateWithTransaction($request, $key);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Updates a resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource|Response
     * @throws BindingResolutionException
     */
    protected function updateWithTransaction(Request $request, int|string $key): Resource|Response
    {
        $query = $this->buildUpdateFetchQuery($request);
        $entity = $this->runUpdateFetchQuery($request, $query, $key);

        $this->authorize($this->resolveAbility('update'), $entity);

        $attributes = $this->retrieve($request);

        $beforeHookResult = $this->beforeUpdate($request, $entity, $attributes);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->repositoryInstance->beforeUpdate($entity, $attributes);

        $beforeSaveHookResult = $this->beforeSave($request, $entity, $attributes);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $this->repositoryInstance->beforeSave($entity, $attributes);

        $this->performUpdate($request, $entity, $attributes);

        $beforeUpdateFreshResult = $this->beforeUpdateFresh($request, $entity);
        if ($this->hookResponds($beforeUpdateFreshResult)) {
            return $beforeUpdateFreshResult;
        }

        $entity = $this->refreshUpdatedEntity($request, $key);

        $this->repositoryInstance->afterSave($entity);

        $afterSaveHookResult = $this->afterSave($request, $entity);
        if ($this->hookResponds($afterSaveHookResult)) {
            return $afterSaveHookResult;
        }

        $this->repositoryInstance->afterUpdate($entity);

        $afterHookResult = $this->afterUpdate($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->getAppendsResolver()->appendToEntity($entity, $request);
        $entity = $this->getRelationsResolver()->guardRelations(
            $entity,
            $this->relationsResolver->requestedRelations($request)
        );

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching entity in update method.
     *
     * @param Request $request
     * @return Builder
     */
    protected function buildUpdateFetchQuery(Request $request): Builder
    {
        return $this->buildFetchQuery($request);
    }

    /**
     * Runs the given query for fetching entity in update method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runUpdateFetchQuery(Request $request, Builder $query, int|string $key): Model
    {
        return $this->runFetchQuery($request, $query, $key);
    }

    /**
     * Fetches the model that has just been updated using the given key.
     *
     * @param Request $request
     * @param int|string $key
     * @return Model
     */
    protected function refreshUpdatedEntity(Request $request, int|string $key): Model
    {
        $query = $this->buildFetchQueryBase($request);

        return $this->runFetchQueryBase($request, $query, $key);
    }

    /**
     * The hook is executed before updating a resource.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     * @return Response|null
     */
    protected function beforeUpdate(Request $request, Model $entity, array &$attributes): ?Response
    {
        return null;
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
        $this->repositoryInstance->performFill($entity, $attributes);
        $this->repositoryInstance->performUpdate($entity);
    }

    /**
     * The hook is executed after updating and before refreshing the resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function beforeUpdateFresh(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * The hook is executed after updating a resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function afterUpdate(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * Deletes a resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource|Response
     * @throws Exception
     */
    public function destroy(Request $request, int|string $key): Resource|Response
    {
        try {
            $this->startTransaction();
            $result = $this->destroyWithTransaction($request, $key);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Deletes a resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource|Response
     * @throws BindingResolutionException
     */
    protected function destroyWithTransaction(Request $request, int|string $key): Resource|Response
    {
        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $forceDeletes = $this->forceDeletes($request, $softDeletes);

        $query = $this->buildDestroyFetchQuery($request, $softDeletes);
        $entity = $this->runDestroyFetchQuery($request, $query, $key);

        if ($this->isResourceTrashed($entity, $softDeletes, $forceDeletes)) {
            abort(404);
        }

        $this->authorize($this->resolveAbility($forceDeletes ? 'forceDelete' : 'delete'), $entity);

        $beforeHookResult = $this->beforeDestroy($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->repositoryInstance->beforeDestroy($entity, $forceDeletes);

        if (!$forceDeletes) {
            $this->performDestroy($entity);

            if ($softDeletes) {
                $beforeDestroyFreshResult = $this->beforeDestroyFresh($request, $entity);

                if ($this->hookResponds($beforeDestroyFreshResult)) {
                    return $beforeDestroyFreshResult;
                }

                $entity = $this->runDestroyFetchQuery($request, $query, $key);
            }
        } else {
            $this->performForceDestroy($entity);
        }

        $this->repositoryInstance->afterDestroy($entity);

        $afterHookResult = $this->afterDestroy($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->getAppendsResolver()->appendToEntity($entity, $request);
        $entity = $this->getRelationsResolver()->guardRelations(
            $entity, $this->relationsResolver->requestedRelations($request)
        );

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching entity in destroy method.
     *
     * @param Request $request
     * @param bool $softDeletes
     * @return Builder
     */
    protected function buildDestroyFetchQuery(Request $request, bool $softDeletes): Builder
    {
        return $this->buildFetchQuery($request)
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
    protected function runDestroyFetchQuery(Request $request, Builder $query, int|string $key): Model
    {
        return $this->runFetchQuery($request, $query, $key);
    }

    /**
     * The hook is executed before deleting a resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function beforeDestroy(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * Deletes or trashes the given entity from database.
     *
     * @param Model $entity
     * @throws Exception
     */
    protected function performDestroy(Model $entity): void
    {
        $this->repositoryInstance->performDestroy($entity, false);
    }

    /**
     * Deletes the given entity from database, even if it is soft deletable.
     *
     * @param Model $entity
     */
    protected function performForceDestroy(Model $entity): void
    {
        $this->repositoryInstance->performDestroy($entity, true);
    }

    /**
     * The hook is executed after deleting and before refreshing the resource.
     * This hook is only called when not using forced deletes
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function beforeDestroyFresh(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * The hook is executed after deleting a resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function afterDestroy(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * Restore previously deleted resource in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource|Response
     * @throws Exception
     */
    public function restore(Request $request, int|string $key): Resource|Response
    {
        try {
            $this->startTransaction();
            $result = $this->restoreWithTransaction($request, $key);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Restore previously deleted resource.
     *
     * @param Request $request
     * @param int|string $key
     * @return Resource|Response
     * @throws BindingResolutionException
     */
    protected function restoreWithTransaction(Request $request, int|string $key): Resource|Response
    {
        $query = $this->buildRestoreFetchQuery($request);
        $entity = $this->runRestoreFetchQuery($request, $query, $key);

        $this->authorize($this->resolveAbility('restore'), $entity);

        $beforeHookResult = $this->beforeRestore($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->repositoryInstance->beforeRestore($entity);

        $this->performRestore($entity);

        $beforeHookResult = $this->beforeRestoreFresh($request, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $entity = $this->runRestoreFetchQuery($request, $query, $key);

        $this->repositoryInstance->afterRestore($entity);

        $afterHookResult = $this->afterRestore($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->getAppendsResolver()->appendToEntity($entity, $request);
        $entity = $this->getRelationsResolver()->guardRelations(
            $entity,
            $this->relationsResolver->requestedRelations($request)
        );

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching entity in restore method.
     *
     * @param Request $request
     * @return Builder
     */
    protected function buildRestoreFetchQuery(Request $request): Builder
    {
        return $this->buildFetchQuery($request)->withTrashed();
    }

    /**
     * Runs the given query for fetching entity in restore method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runRestoreFetchQuery(Request $request, Builder $query, int|string $key): Model
    {
        return $this->runFetchQuery($request, $query, $key);
    }

    /**
     * The hook is executed before force restoring a previously deleted resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function beforeRestore(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * Restores the given entity.
     *
     * @param Model|SoftDeletes $entity
     */
    protected function performRestore(Model $entity): void
    {
        $this->repositoryInstance->performRestore($entity);
    }

    /**
     * The hook is executed after force restoring a previously deleted resource but before
     * refreshing the resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function beforeRestoreFresh(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * The hook is executed after force restoring a previously deleted resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return Response|null
     */
    protected function afterRestore(Request $request, Model $entity): ?Response
    {
        return null;
    }

    /**
     * Fills attributes on the given entity.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performFill(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill(
            Arr::except($attributes, array_keys($entity->getDirty()))
        );
    }

    /**
     * @param Request $request
     * @param array $filterDescriptor
     * @return array
     */
    protected function beforeFilterApplied(Request $request, array $filterDescriptor): array
    {
        return $filterDescriptor;
    }
}
