<?php

namespace Laralord\Orion\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Laralord\Orion\Http\Requests\Request;

trait HandlesRelationManyToManyOperations
{

    /**
     * Sync relation resources.
     *
     * @param Request $request
     * @param int $resourceID
     * @return JsonResponse
     */
    public function sync(Request $request, $resourceID)
    {
        $this->validate($request, [
            'resources' => 'present',
            'detaching' => 'sometimes|boolean'
        ]);

        $beforeHookResult = $this->beforeSync($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) {
            $this->authorize('update', $resourceEntity);
        }

        $syncResult = $resourceEntity->{static::$relation}()->sync(
            $this->prepareResourcePivotFields($this->preparePivotResources($request->get('resources'))), $request->get('detaching', true)
        );

        $afterHookResult = $this->afterSync($request, $syncResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $syncResult['detached'] = array_values($syncResult['detached']);

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
        $this->validate($request, [
            'resources' => 'present'
        ]);

        $beforeHookResult = $this->beforeToggle($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) {
            $this->authorize('update', $resourceEntity);
        }

        $togleResult = $resourceEntity->{static::$relation}()->toggle(
            $this->prepareResourcePivotFields($this->preparePivotResources($request->get('resources')))
        );

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
        $this->validate($request, [
            'resources' => 'present',
            'duplicates' => 'sometimes|boolean'
        ]);

        $beforeHookResult = $this->beforeAttach($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) {
            $this->authorize('update', $resourceEntity);
        }

        if ($request->get('duplicates')) {
            $attachResult = $resourceEntity->{static::$relation}()->attach(
                $this->prepareResourcePivotFields($this->preparePivotResources($request->get('resources')))
            );
        } else {
            $attachResult = $resourceEntity->{static::$relation}()->sync(
                $this->prepareResourcePivotFields($this->preparePivotResources($request->get('resources'))),
                false
            );
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
        $this->validate($request, [
            'resources' => 'present'
        ]);

        $beforeHookResult = $this->beforeDetach($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) {
            $this->authorize('update', $resourceEntity);
        }

        $detachResult = $resourceEntity->{static::$relation}()->detach(
            $this->prepareResourcePivotFields($this->preparePivotResources($request->get('resources')))
        );

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
        $this->validate($request, [
            'pivot' => 'required|array'
        ]);

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
     * Standardizes resources array structure and authorizes individual resources.
     *
     * @param array $resources
     * @return array
     */
    protected function preparePivotResources($resources)
    {
        $resources = $this->standardizePivotResourcesArray($resources);
        $resourceModels = (new static::$model)->{static::$relation}()->getModel()->whereIn('id', array_keys($resources))->get();

        $resources = array_filter($resources, function ($resourceID) use ($resourceModels) {
            /**
             * @var Collection $resourceModels
             */
            $resourceModel = $resourceModels->where('id', $resourceID)->first();

            return $resourceModel && (!$this->authorizationRequired() || Gate::forUser($this->resolveUser())->allows('view', $resourceModel));
        }, ARRAY_FILTER_USE_KEY);

        return $resources;
    }

    /**
     * Standardizes resources array structure.
     *
     * @param array $resources
     * @return array
     */
    protected function standardizePivotResourcesArray($resources)
    {
        $resources = array_wrap($resources);

        $standardizedResources = [];
        foreach ($resources as $key => $pivotFields) {
            if (!is_array($pivotFields)) {
                $standardizedResources[$pivotFields] = [];
            } else {
                $standardizedResources[$key] = $pivotFields;
            }
        }

        return $standardizedResources;
    }

    /**
     * Retrieves only fillable pivot fields and json encodes any objects/arrays.
     *
     * @param array $resources
     * @return array
     */
    protected function prepareResourcePivotFields($resources)
    {
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
}
