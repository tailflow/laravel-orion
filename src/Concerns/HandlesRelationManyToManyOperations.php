<?php

namespace Orion\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Orion\Http\Requests\Request;

trait HandlesRelationManyToManyOperations
{
    /**
     * Attach resource to the relation.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse
     */
    public function attach(Request $request, $parentKey)
    {
        $beforeHookResult = $this->beforeAttach($request, $parentKey);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $this->authorize('update', $parentEntity);

        if ($request->get('duplicates')) {
            $attachResult = $parentEntity->{$this->getRelation()}()->attach(
                $this->prepareResourcePivotFields($this->preparePivotResources($request->get('resources')))
            );
        } else {
            $attachResult = $parentEntity->{$this->getRelation()}()->sync(
                $this->prepareResourcePivotFields($this->preparePivotResources($request->get('resources'))),
                false
            );
        }

        $afterHookResult = $this->afterAttach($request, $attachResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json([
            'attached' => Arr::get($attachResult, 'attached', [])
        ]);
    }

    /**
     * Detach resource to the relation.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse
     */
    public function detach(Request $request, $parentKey)
    {
        $beforeHookResult = $this->beforeDetach($request, $parentKey);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $this->authorize('update', $parentEntity);

        $detachResult = $parentEntity->{$this->getRelation()}()->detach(
            array_keys($this->prepareResourcePivotFields($this->preparePivotResources($request->get('resources'))))
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
     * Sync relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse
     */
    public function sync(Request $request, $parentKey)
    {
        $beforeHookResult = $this->beforeSync($request, $parentKey);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $this->authorize('update', $parentEntity);

        $syncResult = $parentEntity->{$this->getRelation()}()->sync(
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
     * @param int|string $parentKey
     * @return JsonResponse
     */
    public function toggle(Request $request, $parentKey)
    {
        $beforeHookResult = $this->beforeToggle($request, $parentKey);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $this->authorize('update', $parentEntity);

        $toggleResult = $parentEntity->{$this->getRelation()}()->toggle(
            $this->prepareResourcePivotFields($this->preparePivotResources($request->get('resources')))
        );

        $afterHookResult = $this->afterToggle($request, $toggleResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json($toggleResult);
    }

    /**
     * Update relation resource pivot.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string $relatedKey
     * @return JsonResponse
     */
    public function updatePivot(Request $request, $parentKey, $relatedKey)
    {
        $beforeHookResult = $this->beforeUpdatePivot($request, $relatedKey);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $parentEntity = $this->queryBuilder->buildQuery($this->newModelQuery(), $request)
            ->findOrFail($parentKey);

        $this->authorize('update', $parentEntity);

        $updateResult = $parentEntity->{$this->getRelation()}()->updateExistingPivot($relatedKey, $this->preparePivotFields($request->get('pivot', [])));

        $afterHookResult = $this->afterUpdatePivot($request, $updateResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json([
            'updated' => [is_numeric($relatedKey) ? (int) $relatedKey : $relatedKey]
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
        $model = $this->getModel();
        $resources = $this->standardizePivotResourcesArray($resources);
        $resourceModel = (new $model)->{$this->getRelation()}()->getModel();
        $resourceKeyName = $resourceModel->getKeyName();
        $resourceModels = $resourceModel->whereIn($resourceKeyName, array_keys($resources))->get();

        $resources = array_filter($resources, function ($resourceKey) use ($resourceModels, $resourceKeyName) {
            /**
             * @var Collection $resourceModels
             */
            $resourceModel = $resourceModels->where($resourceKeyName, $resourceKey)->first();

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
        $resources = Arr::wrap($resources);

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
            $pivotFields = Arr::only($pivotFields, $this->getPivotFillable());
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

        $pivotJsonFields = $this->getPivotJson();

        foreach ($pivotJsonFields as $pivotJsonField) {
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
     * @param int|string $parentKey
     * @return mixed
     */
    protected function beforeSync(Request $request, $parentKey)
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
     * @param int|string $parentKey
     * @return mixed
     */
    protected function beforeToggle(Request $request, $parentKey)
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
     * @param int|string $parentKey
     * @return mixed
     */
    protected function beforeAttach(Request $request, $parentKey)
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
     * @param int|string $parentKey
     * @return mixed
     */
    protected function beforeDetach(Request $request, $parentKey)
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
     * @param int|string $relatedKey
     * @return mixed
     */
    protected function beforeUpdatePivot(Request $request, $relatedKey)
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
