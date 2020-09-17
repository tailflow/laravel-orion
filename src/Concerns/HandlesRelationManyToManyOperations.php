<?php

namespace Orion\Concerns;

use Illuminate\Database\Eloquent\Builder;
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

        $parentQuery = $this->buildAttachParentQuery($request, $parentKey);
        $parentEntity = $this->runAttachParentQuery($parentQuery, $request, $parentKey);

        $this->authorize('update', $parentEntity);

        $attachResult = $this->performAttach($parentEntity, $request, $request->get('duplicates'), $request->get('resources'));

        $afterHookResult = $this->afterAttach($request, $attachResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json([
            'attached' => Arr::get($attachResult, 'attached', [])
        ]);
    }

    /**
     * Builds Eloquent query for fetching parent entity in attach method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildAttachParentQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in attach method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runAttachParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Attaches the given relation resources to the parent entity.
     *
     * @param Model $parentEntity
     * @param Request $request
     * @param bool $duplicates
     * @param array $resources
     * @return array
     */
    protected function performAttach(Model $parentEntity, Request $request, bool $duplicates, array $resources): array
    {
        $resources = $this->prepareResourcePivotFields($this->preparePivotResources($resources));

        if ($duplicates) {
            return $parentEntity->{$this->getRelation()}()->attach($resources);
        }

        return $parentEntity->{$this->getRelation()}()->sync($resources, false);
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

        $parentQuery = $this->buildDetachParentQuery($request, $parentKey);
        $parentEntity = $this->runDetachParentQuery($parentQuery, $request, $parentKey);

        $this->authorize('update', $parentEntity);

        $detachResult = $this->performDetach($parentEntity, $request, $request->get('resources'));

        $afterHookResult = $this->afterDetach($request, $detachResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json([
            'detached' => array_values($request->get('resources', []))
        ]);
    }

    /**
     * Builds Eloquent query for fetching parent entity in detach method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildDetachParentQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in detach method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runDetachParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Detaches the given relation resources from the parent entity.
     *
     * @param Model $parentEntity
     * @param Request $request
     * @param array $resources
     * @return array
     */
    protected function performDetach(Model $parentEntity, Request $request, array $resources): array
    {
        $resources = $this->prepareResourcePivotFields($this->preparePivotResources($resources));

        return $parentEntity->{$this->getRelation()}()->detach(array_keys($resources));
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

        $parentQuery = $this->buildSyncParentQuery($request, $parentKey);
        $parentEntity = $this->runSyncParentQuery($parentQuery, $request, $parentKey);

        $this->authorize('update', $parentEntity);

        $syncResult = $this->performSync($parentEntity, $request, $request->get('detaching', true), $request->get('resources'));

        $afterHookResult = $this->afterSync($request, $syncResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json($syncResult);
    }

    /**
     * Builds Eloquent query for fetching parent entity in sync method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildSyncParentQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in sync method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runSyncParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Sync the given relation resources on the parent entity.
     *
     * @param Model $parentEntity
     * @param Request $request
     * @param bool $detaching
     * @param array $resources
     * @return array
     */
    protected function performSync(Model $parentEntity, Request $request, bool $detaching, array $resources): array
    {
        $resources = $this->prepareResourcePivotFields($this->preparePivotResources($resources));

        $syncResult = $parentEntity->{$this->getRelation()}()->sync(
            $resources, $detaching
        );

        $syncResult['detached'] = array_values($syncResult['detached']);

        return $syncResult;
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

        $parentQuery = $this->buildToggleParentQuery($request, $parentKey);
        $parentEntity = $this->runToggleParentQuery($parentQuery, $request, $parentKey);

        $this->authorize('update', $parentEntity);

        $toggleResult = $this->performToggle($parentEntity, $request, $request->get('resources'));

        $afterHookResult = $this->afterToggle($request, $toggleResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json($toggleResult);
    }

    /**
     * Builds Eloquent query for fetching parent entity in toggle method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildToggleParentQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in toggle method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runToggleParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Toggles the given relation resources on the parent entity.
     *
     * @param Model $parentEntity
     * @param Request $request
     * @param array $resources
     * @return array
     */
    protected function performToggle(Model $parentEntity, Request $request, array $resources): array
    {
        $resources = $this->prepareResourcePivotFields($this->preparePivotResources($resources));

        return $parentEntity->{$this->getRelation()}()->toggle($resources);
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

        $parentQuery = $this->buildUpdatePivotParentQuery($request, $parentKey);
        $parentEntity = $this->runUpdatePivotParentQuery($parentQuery, $request, $parentKey);

        $this->authorize('update', $parentEntity);

        $updateResult = $this->performUpdatePivot($parentEntity, $request, $relatedKey, $request->get('pivot', []));

        $afterHookResult = $this->afterUpdatePivot($request, $updateResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json([
            'updated' => [is_numeric($relatedKey) ? (int) $relatedKey : $relatedKey]
        ]);
    }

    /**
     * Builds Eloquent query for fetching parent entity in update pivot method.
     *
     * @param Request $request
     * @param string|int $parentKey
     * @return Builder
     */
    protected function buildUpdatePivotParentQuery(Request $request, $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in update pivot method.
     *
     * @param Builder $query
     * @param Request $request
     * @param string|int $parentKey
     * @return Model
     */
    protected function runUpdatePivotParentQuery(Builder $query, Request $request, $parentKey): Model
    {
        return $this->runParentFetchQuery($query, $request, $parentKey);
    }

    /**
     * Updates relation resource pivot.
     *
     * @param Model $parentEntity
     * @param Request $request
     * @param string|int $relatedKey
     * @param array $pivot
     * @return array
     */
    protected function performUpdatePivot(Model $parentEntity, Request $request, $relatedKey, array $pivot): array
    {
        $pivot = $this->preparePivotFields($pivot);

        return $parentEntity->{$this->getRelation()}()->updateExistingPivot($relatedKey, $pivot);
    }

    /**
     * Standardizes resources array structure and authorizes individual resources.
     *
     * @param array $resources
     * @return array
     */
    protected function preparePivotResources(array $resources): array
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
    protected function prepareResourcePivotFields(array $resources)
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
    protected function preparePivotFields(array $pivotFields)
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
    protected function castPivotJsonFields(Model $entity)
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
    protected function afterSync(Request $request, array &$syncResult)
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
    protected function afterToggle(Request $request, array &$toggleResult)
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
     * @param array $attachResult
     * @return mixed
     */
    protected function afterAttach(Request $request, array &$attachResult)
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
     * @param array $detachResult
     * @return mixed
     */
    protected function afterDetach(Request $request, array &$detachResult)
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
    protected function afterUpdatePivot(Request $request, array &$updateResult)
    {
        return null;
    }
}
