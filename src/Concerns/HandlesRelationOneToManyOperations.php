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
        $parentQuery = $this->buildAssociateParentQuery($request, $parentKey);
        $parentEntity = $this->runAssociateParentQuery($parentQuery, $request, $parentKey);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildAssociateQuery($request, $parentEntity, $requestedRelations);
        $entity = $this->runAssociateQuery($query, $request, $parentEntity, $request->get('related_key'));

        $beforeHookResult = $this->beforeAssociate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize('view', $parentEntity);
        $this->authorize('update', $entity);

        $this->performAssociate($parentEntity, $entity, $request);

        $afterHookResult = $this->afterAssociate($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in associate method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildAssociateParentQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in associate method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runAssociateParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in associate method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildAssociateQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations);
    }

    /**
     * Runs the given query for fetching relation entity in associate method.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runAssociateQuery(Relation $query, Request $request, Model $parentEntity, $relatedKey): Model
    {
        return $this->runRelationFetchQuery($query, $request, $parentEntity, $relatedKey);
    }

    /**
     * Associates the given entity with parent entity.
     *
     * @param Model $parentEntity
     * @param Model $entity
     * @param Request $request
     */
    protected function performAssociate(Model $parentEntity, Model $entity, Request $request): void
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
        $parentQuery = $this->buildDissociateParentQuery($request, $parentKey);
        $parentEntity = $this->runDissociateParentQuery($parentQuery, $request, $parentKey);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildDissociateQuery($request, $parentEntity, $requestedRelations);
        $entity = $this->runDissociateQuery($query, $request, $parentEntity, $relatedKey);

        $beforeHookResult = $this->beforeDissociate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize('update', $entity);

        $this->performDissociate($parentEntity, $entity, $request);

        $afterHookResult = $this->afterDissociate($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return $this->entityResponse($entity);
    }

    /**
     * Builds Eloquent query for fetching parent entity in dissociate method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildDissociateParentQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in dissociate method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runDissociateParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Builds Eloquent query for fetching relation entity in dissociate method.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $requestedRelations
     * @return Relation
     */
    protected function buildDissociateQuery(Request $request, Model $parentEntity, array $requestedRelations): Relation
    {
        return $this->buildRelationFetchQuery($request, $parentEntity, $requestedRelations);
    }

    /**
     * Runs the given query for fetching relation entity in dissociate method.
     *
     * @param Relation $query
     * @param Request $request
     * @param Model $parentEntity
     * @param string|int $relatedKey
     * @return Model
     */
    protected function runDissociateQuery(Relation $query, Request $request, Model $parentEntity, $relatedKey): Model
    {
        return $this->runRelationFetchQuery($query, $request, $parentEntity, $relatedKey);
    }

    /**
     * Dissociates the given entity from its parent entity.
     *
     * @param Model $parentEntity
     * @param Model $entity
     * @param Request $request
     */
    protected function performDissociate(Model $parentEntity, Model $entity, Request $request): void
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
