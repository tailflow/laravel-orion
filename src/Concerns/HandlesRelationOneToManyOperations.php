<?php

namespace Orion\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\Resource;

trait HandlesRelationOneToManyOperations
{
    /**
     * Associates resource with another resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Resource
     */
    public function associate(Request $request, $parentKey)
    {
        $parentQuery = $this->buildAssociateParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runAssociateParentFetchQuery($request, $parentQuery, $parentKey);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildAssociateFetchQuery($request, $parentEntity, $requestedRelations);
        $entity = $this->runAssociateFetchQuery($request, $query, $parentEntity, $request->get('related_key'));

        $beforeHookResult = $this->beforeAssociate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize('view', $parentEntity);
        $this->authorize('update', $entity);

        $this->performAssociate($request, $parentEntity, $entity);

        $entity = $this->runAssociateFetchQuery($request, $query, $parentEntity, $request->get('related_key'));

        $afterHookResult = $this->afterAssociate($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in associate method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildAssociateParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in associate method.
     *
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runAssociateParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in associate method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildAssociateFetchQuery(Request $request, Model $parentEntity, array $requestedRelations): Builder
    {
        $relatedModel = $parentEntity->{$this->getRelation()}()->getModel();

        return $this->relationQueryBuilder->buildQuery($relatedModel->query(), $request)
            ->with($this->relationsResolver->requestedRelations($request));
    }

    /**
     * Runs the given query for fetching relation entity in associate method.
     *
     * @param Request $request
     * @param Builder $query
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runAssociateFetchQuery(Request $request, Builder $query, Model $parentEntity, $relatedKey): Model
    {
        return $query->where($this->resolveQualifiedKeyName(), $relatedKey)->firstOrFail();
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
        $parentEntity->{$this->getRelation()}()->save($entity);
    }

    /**
     * Disassociates resource from another resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string $relatedKey
     * @return Resource
     */
    public function dissociate(Request $request, $parentKey, $relatedKey)
    {
        $parentQuery = $this->buildDissociateParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runDissociateParentFetchQuery($request, $parentQuery, $parentKey);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildDissociateFetchQuery($request, $parentEntity, $requestedRelations);
        $entity = $this->runDissociateFetchQuery($request, $query, $parentEntity, $relatedKey);

        $beforeHookResult = $this->beforeDissociate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize('update', $entity);

        $this->performDissociate($request, $parentEntity, $entity);

        $entity = $this->relationQueryBuilder->buildQuery($entity::query(), $request)
            ->with($this->relationsResolver->requestedRelations($request))->first();

        $afterHookResult = $this->afterDissociate($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $entity = $this->relationsResolver->guardRelations($entity, $requestedRelations);

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in dissociate method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildDissociateParentFetchQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in dissociate method.
     *
     * @param Request $request
     * @param Builder $query
     * @param string|int $parentKey
     * @return Model
     */
    protected function runDissociateParentFetchQuery(Request $request, Builder $query, $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in dissociate method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildDissociateFetchQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations);
    }

    /**
     * Runs the given query for fetching relation entity in dissociate method.
     *
     * @param Request $request
     * @param Relation $query
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runDissociateFetchQuery(Request $request, Relation $query, Model $parentEntity, $relatedKey): Model
    {
        return $this->runRelationFetchQuery($request, $query, $parentEntity, $relatedKey);
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
        $foreignKeyName = $parentEntity->{$this->getRelation()}()->getForeignKeyName();

        $entity->{$foreignKeyName} = null;
        $entity->save();
    }

    /**
     * The hook is executed before associating relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function beforeAssociate(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * The hook is executed after associating relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterAssociate(Request $request, Model $entity)
    {
        return null;
    }

    /**
     * The hook is executed before dissociating relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function beforeDissociate(Request $request, Model $parentEntity, Model $entity)
    {
        return null;
    }

    /**
     * The hook is executed after dissociating relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterDissociate(Request $request, Model $entity)
    {
        return null;
    }
}
