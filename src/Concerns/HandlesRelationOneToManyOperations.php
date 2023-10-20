<?php

declare(strict_types=1);

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\Resource;
use Symfony\Component\HttpFoundation\Response;

trait HandlesRelationOneToManyOperations
{
    /**
     * Associates resource with another resource in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Resource|Response
     * @throws Exception
     */
    public function associate(Request $request, int|string $parentKey): Resource|Response
    {
        try {
            $this->startTransaction();
            $result = $this->runAssociateOperation($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Associates resource with another resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Resource|Response
     * @throws BindingResolutionException
     */
    protected function runAssociateOperation(Request $request, int|string $parentKey): Resource|Response
    {
        $parentQuery = $this->buildAssociateParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runAssociateParentFetchQuery($request, $parentQuery, $parentKey);

        $query = $this->buildAssociateFetchQuery($request, $parentEntity);
        $entity = $this->runAssociateFetchQuery($request, $query, $parentEntity, $request->get('related_key'));

        $beforeHookResult = $this->beforeAssociate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize($this->resolveAbility('show'), $parentEntity);
        $this->authorize($this->resolveAbility('update'), [$entity, $parentEntity]);

        $this->performAssociate($request, $parentEntity, $entity);

        $entity = $this->runAssociateFetchQuery($request, $query, $parentEntity, $request->get('related_key'));

        $afterHookResult = $this->afterAssociate($request, $parentEntity, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations(
            $entity, $this->relationsResolver->requestedRelations($request)
        );

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in associate method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildAssociateParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in associate method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runAssociateParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in associate method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Builder
     */
    protected function buildAssociateFetchQuery(
        Request $request,
        Model $parentEntity
    ): Builder {
        $relatedModel = $parentEntity->{$this->relation()}()->getModel();

        return $this->relationQueryBuilder->buildQuery($relatedModel->query(), $request);
    }

    /**
     * Runs the given query for fetching relation entity in associate method.
     *
     * @param Request $request
     * @param Builder $query
     * @param Model $parentEntity
     * @param int|string|null $relatedKey
     * @return Model
     */
    protected function runAssociateFetchQuery(Request $request, Builder $query, Model $parentEntity, int|string|null $relatedKey): Model
    {
        return $query->where($this->resolveQualifiedKeyName(), $relatedKey)->firstOrFail();
    }

    /**
     * The hook is executed before associating relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return Response|null
     */
    protected function beforeAssociate(Request $request, Model $parentEntity, Model $entity): ?Response
    {
        return null;
    }

    /**
     * Associates the given entity with parent entity.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     */
    protected function performAssociate(Request $request, Model $parentEntity, Model $entity): void
    {
        $parentEntity->{$this->relation()}()->save($entity);
    }

    /**
     * The hook is executed after associating relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return Response|null
     */
    protected function afterAssociate(Request $request, Model $parentEntity, Model $entity): ?Response
    {
        return null;
    }

    /**
     * Disassociates resource from another resource in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource|Response
     * @throws Exception
     */
    public function dissociate(Request $request, int|string $parentKey, int|string|null $relatedKey): Resource|Response
    {
        try {
            $this->startTransaction();
            $result = $this->runDissociateOperation($request, $parentKey, $relatedKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Disassociates resource from another resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string|null $relatedKey
     * @return Resource|Response
     * @throws BindingResolutionException
     */
    protected function runDissociateOperation(Request $request, int|string $parentKey, int|string|null $relatedKey): Resource|Response
    {
        $parentQuery = $this->buildDissociateParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runDissociateParentFetchQuery($request, $parentQuery, $parentKey);

        $query = $this->buildDissociateFetchQuery($request, $parentEntity);
        $entity = $this->runDissociateFetchQuery($request, $query, $parentEntity, $relatedKey);

        $beforeHookResult = $this->beforeDissociate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize($this->resolveAbility('update'), [$entity, $parentEntity]);

        $this->performDissociate($request, $parentEntity, $entity);

        $entity = $this->relationQueryBuilder->buildQuery($entity::query(), $request)
            ->where(
                $this->resolveQualifiedKeyName(),
                $entity->{$this->keyName()}
            )->firstOrFail();

        $afterHookResult = $this->afterDissociate($request, $parentEntity, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations(
            $entity, $this->relationsResolver->requestedRelations($request)
        );

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in dissociate method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildDissociateParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in dissociate method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runDissociateParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in dissociate method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Relation
     */
    protected function buildDissociateFetchQuery(
        Request $request,
        Model $parentEntity
    ): Relation {
        return $this->buildRelationFetchQuery($request, $parentEntity);
    }

    /**
     * Runs the given query for fetching relation entity in dissociate method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @param int|string|null $relatedKey
     * @return Model
     */
    protected function runDissociateFetchQuery(
        Request $request,
        Relation $query,
        Model $parentEntity,
        int|string|null $relatedKey
    ): Model {
        return $this->runRelationFetchQuery($request, $query, $parentEntity, $relatedKey);
    }

    /**
     * The hook is executed before dissociating relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return Response|null
     */
    protected function beforeDissociate(Request $request, Model $parentEntity, Model $entity): ?Response
    {
        return null;
    }

    /**
     * Dissociates the given entity from its parent entity.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     */
    protected function performDissociate(Request $request, Model $parentEntity, Model $entity): void
    {
        $foreignKeyName = $parentEntity->{$this->relation()}()->getForeignKeyName();

        $entity->{$foreignKeyName} = null;
        $entity->save();
    }

    /**
     * The hook is executed after dissociating relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return Response|null
     */
    protected function afterDissociate(Request $request, Model $parentEntity, Model $entity): ?Response
    {
        return null;
    }
}
