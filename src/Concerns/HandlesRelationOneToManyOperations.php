<?php

namespace Orion\Concerns;

use Illuminate\Database\Eloquent\Model;
use Orion\Exceptions\BindingException;
use Orion\Http\Requests\Request;

trait HandlesRelationOneToManyOperations
{
    /**
     * Associates resource with another resource.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Resource
     * @throws BindingException
     */
    public function associate(Request $request, $parentKey)
    {
        if (!static::$associatingRelation) {
            throw new BindingException('$associatingRelation property is not set on '.static::class);
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $entity = $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($this->relationQueryBuilder->requestedRelations($request))
            ->findOrFail($request->get('related_key'));

        $beforeHookResult = $this->beforeAssociate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $entity->{static::$associatingRelation}()->associate($parentEntity);

        if ($this->authorizationRequired()) {
            $this->authorize('view', $parentEntity);
            $this->authorize('update', $entity);
        }

        $entity->save();

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
     * @throws BindingException
     */
    public function dissociate(Request $request, $parentKey, $relatedKey)
    {
        if (!static::$associatingRelation) {
            throw new BindingException('$associatingRelation property is not set on '.static::class);
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $entity = $this->relationQueryBuilder->buildQuery($this->newRelationQuery($parentEntity), $request)
            ->with($this->relationQueryBuilder->requestedRelations($request))
            ->findOrFail($relatedKey);

        $beforeHookResult = $this->beforeDissociate($request, $parentEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        if ($this->authorizationRequired()) {
            $this->authorize('update', $entity);
        }

        $entity->{static::$associatingRelation}()->dissociate();
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
