<?php

namespace Orion\Concerns;

use Illuminate\Database\Eloquent\Model;
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
        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $parentModel = $this->getModel();
        $relatedModel = (new $parentModel)->{$this->getRelation()}()->getModel();

        $entity = $this->relationQueryBuilder->buildQuery($relatedModel->query(), $request)
            ->with($this->relationsResolver->requestedRelations($request))
            ->findOrFail($request->get('related_key'));

        $beforeHookResult = $this->beforeAssociate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        if ($this->authorizationRequired()) {
            $this->authorize('view', $parentEntity);
            $this->authorize('update', $entity);
        }

        $parentEntity->{$this->getRelation()}()->save($entity);

        $afterHookResult = $this->afterAssociate($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return $this->entityResponse($entity);
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
        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $entity = $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($this->relationsResolver->requestedRelations($request))
            ->findOrFail($relatedKey);

        $beforeHookResult = $this->beforeDissociate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        if ($this->authorizationRequired()) {
            $this->authorize('update', $entity);
        }

        $parentModel = $this->getModel();
        $foreignKeyName = (new $parentModel)->{$this->getRelation()}()->getForeignKeyName();

        $entity->{$foreignKeyName} = null;
        $entity->save();

        $afterHookResult = $this->afterDissociate($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return $this->entityResponse($entity);
    }

    /**
     * The hook is executed before associating relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param Model $entity
     * @return mixed
     */
    protected function beforeAssociate(Request $request, $parentEntity, $entity)
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
    protected function afterAssociate(Request $request, $entity)
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
    protected function beforeDissociate(Request $request, $parentEntity, $entity)
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
    protected function afterDissociate(Request $request, $entity)
    {
        return null;
    }
}
