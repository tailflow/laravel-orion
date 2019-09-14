<?php


namespace Laralord\Orion\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Laralord\Orion\Http\Requests\Request;

trait HandlesRelationOneToManyOperations
{
    /**
     * Associates resource with another resource.
     *
     * @param Request $request
     * @param int $resourceID
     * @return Resource
     * @throws Exception
     */
    public function associate(Request $request, $resourceID)
    {
        $this->validate($request, [
            'related_id' => 'required'
        ]);

        if (!static::$associatingRelation) {
            throw new Exception('$associatingRelation property is not set on '.static::class);
        }

        $relatedID = $request->get('related_id');

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        $entity = $resourceEntity->{static::$relation}()->getRelated()->findOrFail($relatedID);

        $beforeHookResult = $this->beforeAssociate($request, $resourceEntity, $entity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        if ($this->authorizationRequired()) {
            $this->authorize('show', $resourceEntity);
            $this->authorize('update', $entity);
        }

        $entity->{static::$associatingRelation}()->associate($resourceEntity);

        $afterHookResult = $this->afterAssociate($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return new static::$resource($entity);
    }

    /**
     * Disassociates resource from another resource.
     *
     * @param Request $request
     * @param int $resourceID
     * @param int $relatedID
     * @return Resource
     * @throws Exception
     */
    public function dissociate(Request $request, $resourceID, $relatedID)
    {
        if (!static::$associatingRelation) {
            throw new Exception('$associatingRelation property is not set on '.static::class);
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->findOrFail($relatedID);

        $beforeHookResult = $this->beforeDissociate($request, $resourceEntity, $entity);
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

        return new static::$resource($entity);
    }

    /**
     * The hook is executed before associating relation resource.
     *
     * @param Request $request
     * @param Model $resourceEntity
     * @param Model $entity
     * @return mixed
     */
    protected function beforeAssociate(Request $request, $resourceEntity, $entity)
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
     * @param Model $resourceEntity
     * @param Model $entity
     * @return mixed
     */
    protected function beforeDissociate(Request $request, $resourceEntity, $entity)
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
