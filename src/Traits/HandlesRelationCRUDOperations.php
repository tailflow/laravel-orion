<?php

namespace Laralord\Orion\Traits;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\Resources\Json\ResourceCollection;
use InvalidArgumentException;
use Laralord\Orion\Http\Requests\Request;

trait HandlesRelationCRUDOperations
{
    /**
     * Fetch the list of relation resources.
     *
     * @param Request $request
     * @param int $resourceID
     * @return ResourceCollection
     */
    public function index(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeIndex($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        if ($this->authorizationRequired()) {
            $this->authorize('index', static::$model);
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        $entities = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->paginate();

        if (count($this->pivotJson)) {
            $entities->getCollection()->transform(function ($entity) {
                return $this->castPivotJsonFields($entity);
            });
        }

        $afterHookResult = $this->afterIndex($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return static::$collectionResource ? new static::$collectionResource($entities) : static::$resource::collection($entities);
    }

    /**
     * Create new relation resource.
     *
     * @param Request $request
     * @param int $resourceID
     * @return Resource
     */
    public function store(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeStore($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $relationModelClass = $this->getRelationModelClass();

        if ($this->authorizationRequired()) {
            $this->authorize('store', $relationModelClass);
        }

        /**
         * @var Model $entity
         */
        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);

        $entity = new $relationModelClass();
        $entity->fill($request->only($entity->getFillable()));

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        if (!$resourceEntity->{static::$relation}() instanceof BelongsTo) {
            $resourceEntity->{static::$relation}()->save($entity, $this->preparePivotFields($request->get('pivot', [])));
        } else {
            $entity->save();
            $resourceEntity->{static::$relation}()->associate($entity);
        }

        $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->findOrFail($entity->getKey());

        if (count($this->pivotJson)) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterSaveHookResult = $this->afterSave($request, $entity);
        if ($this->hookResponds($afterSaveHookResult)) {
            return $afterSaveHookResult;
        }

        $afterHookResult = $this->afterStore($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return new static::$resource($entity);
    }

    /**
     * Fetch a relation resource.
     *
     * @param Request $request
     * @param int $resourceID
     * @param int|null $relatedID
     * @return Resource
     */
    public function show(Request $request, $resourceID, $relatedID = null)
    {
        $beforeHookResult = $this->beforeShow($request, $relatedID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);

        if ($this->isOneToOneRelation($resourceEntity)) {
            $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->firstOrFail();
        } else {
            $this->abortIfMissingRelatedID($relatedID);
            $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->findOrFail($relatedID);
        }

        if ($this->authorizationRequired()) {
            $this->authorize('show', $entity);
        }

        if (count($this->pivotJson)) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterHookResult = $this->afterShow($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return new static::$resource($entity);
    }

    /**
     * Update a relation resource.
     *
     * @param Request $request
     * @param int $resourceID
     * @param int|null $relatedID
     * @return Resource
     */
    public function update(Request $request, $resourceID, $relatedID = null)
    {
        $beforeHookResult = $this->beforeUpdate($request, $relatedID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);

        if ($this->isOneToOneRelation($resourceEntity)) {
            $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->firstOrFail();
        } else {
            $this->abortIfMissingRelatedID($relatedID);
            $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->findOrFail($relatedID);
        }

        if ($this->authorizationRequired()) {
            $this->authorize('update', $entity);
        }

        $entity->fill($request->only($entity->getFillable()));

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) {
            return $beforeSaveHookResult;
        }

        $entity->save();

        $relation = $resourceEntity->{static::$relation}();
        if ($relation instanceof BelongsToMany || $relation instanceof MorphToMany) {
            $relation->updateExistingPivot($relatedID, $this->preparePivotFields($request->get('pivot', [])));

            if ($this->isOneToOneRelation($resourceEntity)) {
                $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->firstOrFail();
            } else {
                $this->abortIfMissingRelatedID($relatedID);
                $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->findOrFail($relatedID);
            }
        }

        if (count($this->pivotJson)) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterSaveHookResult = $this->afterSave($request, $entity);
        if ($this->hookResponds($afterSaveHookResult)) {
            return $afterSaveHookResult;
        }

        $afterHookResult = $this->afterUpdate($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return new static::$resource($entity);
    }

    /**
     * Delete a relation resource.
     *
     * @param Request $request
     * @param int $resourceID
     * @param int|null $relatedID
     * @return Resource
     * @throws Exception
     */
    public function destroy(Request $request, $resourceID, $relatedID = null)
    {
        $beforeHookResult = $this->beforeDestroy($request, $relatedID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);

        if ($this->isOneToOneRelation($resourceEntity)) {
            $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->firstOrFail();
        } else {
            $this->abortIfMissingRelatedID($relatedID);
            $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->findOrFail($relatedID);
        }

        if ($this->authorizationRequired()) {
            $this->authorize('destroy', $entity);
        }

        $entity->delete();

        if (count($this->pivotJson)) {
            $entity = $this->castPivotJsonFields($entity);
        }

        $afterHookResult = $this->afterDestroy($request, $entity);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return new static::$resource($entity);
    }

    /**
     * Determines whether controller relation is one-to-one or not.
     *
     * @param Model $resourceEntity
     * @return bool
     */
    protected function isOneToOneRelation($resourceEntity)
    {
        $relation = $resourceEntity->{static::$relation}();
        return $relation instanceof HasOne || $relation instanceof MorphOne || $relation instanceof BelongsTo;
    }

    /**
     * Throws exception, if related ID is undefined and relation type is not one-to-one.
     *
     * @param int|null $relatedID
     */
    protected function abortIfMissingRelatedID($relatedID)
    {
        if ($relatedID) {
            return;
        }
        throw new InvalidArgumentException('Relation ID is required, if relation type is not one-to-one');
    }


    /**
     * Get relation model class from the relation.
     *
     * @return string
     */
    protected function getRelationModelClass()
    {
        return get_class(with(new static::$model)->{static::$relation}()->getModel());
    }

    /**
     * The hooks is executed before fetching the list of relation resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeIndex(Request $request)
    {
        return null;
    }

    /**
     * The hooks is executed after fetching the list of relation resources.
     *
     * @param Request $request
     * @param LengthAwarePaginator $entities
     * @return mixed
     */
    protected function afterIndex(Request $request, LengthAwarePaginator $entities)
    {
        return null;
    }

    /**
     * The hook is executed before creating new relation resource.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeStore(Request $request)
    {
        return null;
    }

    /**
     * The hook is executed after creating new relation resource.
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
     * The hook is executed before fetching relation resource.
     *
     * @param Request $request
     * @param int|null $id
     * @return mixed
     */
    protected function beforeShow(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after fetching a relation resource
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
     * The hook is executed before updating a relation resource.
     *
     * @param Request $request
     * @param int|null $id
     * @return mixed
     */
    protected function beforeUpdate(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after updating a relation resource.
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
     * The hook is executed before deleting a relation resource.
     *
     * @param Request $request
     * @param int|null $id
     * @return mixed
     */
    protected function beforeDestroy(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after deleting a relation resource.
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
     * The hook is executed before creating or updating a relation resource.
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
     * The hook is executed after creating or updating a relation resource.
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
