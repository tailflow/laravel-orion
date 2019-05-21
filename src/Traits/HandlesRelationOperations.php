<?php

namespace Laralord\Orion\Traits;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use InvalidArgumentException;

trait HandlesRelationOperations
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
            'related_id' => 'required|integer'
        ]);

        if (!static::$associatingRelation) {
            throw new Exception('$associatingRelation property is not set on '.__CLASS__);
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
            throw new Exception('$associatingRelation property is not set on '.__CLASS__);
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
     * Sync relation resources.
     *
     * @param Request $request
     * @param int $resourceID
     * @return JsonResponse
     */
    public function sync(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeSync($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) {
            $this->authorize('update', $resourceEntity);
        }

        $syncResult = $resourceEntity->{static::$relation}()->sync($this->prepareResourcePivotFields($request->get('resources')), $request->get('detaching', true));

        $afterHookResult = $this->afterSync($request, $syncResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json($syncResult);
    }

    /**
     * Toggle relation resources.
     *
     * @param Request $request
     * @param int $resourceID
     * @return JsonResponse
     */
    public function toggle(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeToggle($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) {
            $this->authorize('update', $resourceEntity);
        }

        $togleResult = $resourceEntity->{static::$relation}()->toggle($this->prepareResourcePivotFields($request->get('resources')));

        $afterHookResult = $this->afterToggle($request, $togleResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json($togleResult);
    }

    /**
     * Attach resource to the relation.
     *
     * @param Request $request
     * @param int $resourceID
     * @return JsonResponse
     */
    public function attach(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeAttach($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) {
            $this->authorize('update', $resourceEntity);
        }

        if ($request->get('duplicates')) {
            $attachResult = $resourceEntity->{static::$relation}()->attach($this->prepareResourcePivotFields($request->get('resources')));
        } else {
            $attachResult = $resourceEntity->{static::$relation}()->sync($this->prepareResourcePivotFields($request->get('resources')), false);
        }

        $afterHookResult = $this->afterAttach($request, $attachResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json([
            'attached' => array_get($attachResult, 'attached', [])
        ]);
    }

    /**
     * Detach resource to the relation.
     *
     * @param Request $request
     * @param int $resourceID
     * @return JsonResponse
     */
    public function detach(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeDetach($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) {
            $this->authorize('update', $resourceEntity);
        }

        $detachResult = $resourceEntity->{static::$relation}()->detach($this->prepareResourcePivotFields($request->get('resources')));

        $afterHookResult = $this->afterDetach($request, $detachResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json([
            'detached' => array_values($request->get('resources', []))
        ]);
    }

    /**
     * Update relation resource pivot.
     *
     * @param Request $request
     * @param int $resourceID
     * @param int $relationID
     * @return JsonResponse
     */
    public function updatePivot(Request $request, $resourceID, $relationID)
    {
        $beforeHookResult = $this->beforeUpdatePivot($request, $relationID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) {
            $this->authorize('update', $resourceEntity);
        }

        $updateResult = $resourceEntity->{static::$relation}()->updateExistingPivot($relationID, $this->preparePivotFields($request->get('pivot', [])));

        $afterHookResult = $this->afterUpdatePivot($request, $updateResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json([
            'updated' => [is_numeric($relationID) ? (int) $relationID : $relationID]
        ]);
    }

    /**
     * Retrieves only fillable pivot fields and json encodes any objects/arrays.
     *
     * @param array $resources
     * @return array
     */
    protected function prepareResourcePivotFields($resources)
    {
        $resources = array_wrap($resources);

        foreach ($resources as $key => &$pivotFields) {
            if (!is_array($pivotFields)) {
                continue;
            }
            $pivotFields = array_only($pivotFields, $this->pivotFillable);
            $pivotFields = $this->preparePivotFields($pivotFields);
        }

        return $resources;
    }

    /**
     * Json encodes any objects/arrays of the given pivot fields.
     *
     * @param array $pivotFields
     * @return array mixed
     */
    protected function preparePivotFields($pivotFields)
    {
        foreach ($pivotFields as &$field) {
            if (is_array($field) || is_object($field)) {
                $field = json_encode($field);
            }
        }

        return $pivotFields;
    }

    /**
     * Casts pivot json fields to array on the given entity.
     *
     * @param Model $entity
     * @return Model
     */
    protected function castPivotJsonFields($entity)
    {
        if (!$entity->pivot) {
            return $entity;
        }

        foreach ($this->pivotJson as $pivotJsonField) {
            if (!$entity->pivot->{$pivotJsonField}) {
                continue;
            }
            $entity->pivot->{$pivotJsonField} = json_decode($entity->pivot->{$pivotJsonField}, true);
        }
        return $entity;
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

    /**
     * The hook is executed before syncing relation resources.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeSync(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after syncing relation resources.
     *
     * @param Request $request
     * @param array $syncResult
     * @return mixed
     */
    protected function afterSync(Request $request, &$syncResult)
    {
        return null;
    }

    /**
     * The hook is executed before toggling relation resources.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeToggle(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after toggling relation resources.
     *
     * @param Request $request
     * @param array $toggleResult
     * @return mixed
     */
    protected function afterToggle(Request $request, &$toggleResult)
    {
        return null;
    }

    /**
     * The hook is executed before attaching relation resource.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeAttach(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after attaching relation resource.
     *
     * @param Request $request
     * @param array $toggleResult
     * @return mixed
     */
    protected function afterAttach(Request $request, &$toggleResult)
    {
        return null;
    }

    /**
     * The hook is executed before detaching relation resource.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeDetach(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after detaching relation resource.
     *
     * @param Request $request
     * @param array $toggleResult
     * @return mixed
     */
    protected function afterDetach(Request $request, &$toggleResult)
    {
        return null;
    }

    /**
     * The hook is executed before updating relation resource pivot.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeUpdatePivot(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after updating relation resource pivot.
     *
     * @param Request $request
     * @param array $updateResult
     * @return mixed
     */
    protected function afterUpdatePivot(Request $request, &$updateResult)
    {
        return null;
    }

    /**
     * Get Eloquent query builder for the relation model and apply filters, searching and sorting.
     *
     * @param Request $request
     * @param Model $resourceEntity
     * @return Builder
     */
    protected function buildRelationQuery(Request $request, $resourceEntity)
    {
        /**
         * @var Builder $query
         */
        $query = $resourceEntity->{static::$relation}();

        // only for index method (well, and show method also, but it does not make sense to sort, filter or search data in the show method via query parameters...)
        if ($request->isMethod('GET')) {
            $this->applyFiltersToQuery($request, $query);
            $this->applySearchingToQuery($request, $query);
            $this->applySortingToQuery($request, $query);
        }

        return $query;
    }

    /**
     * Get custom query builder, if any, otherwise use default; apply filters, searching and sorting.
     *
     * @param Request $request
     * @param Model $resourceEntity
     * @return Builder
     */
    protected function buildRelationMethodQuery(Request $request, $resourceEntity)
    {
        $method = debug_backtrace()[1]['function'];
        $customQueryMethod = 'buildRelation'.ucfirst($method).'Query';

        if (method_exists($this, $customQueryMethod)) {
            return $this->{$customQueryMethod}($request);
        }
        return $this->buildRelationQuery($request, $resourceEntity);
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
}
