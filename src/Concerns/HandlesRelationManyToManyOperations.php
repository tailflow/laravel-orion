<?php

declare(strict_types=1);

namespace Orion\Concerns;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Orion\Http\Requests\Request;
use Symfony\Component\HttpFoundation\Response;

trait HandlesRelationManyToManyOperations
{
    /**
     * Attach resource to the relation in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function attach(Request $request, int|string $parentKey): JsonResponse|Response
    {
        try {
            $this->startTransaction();
            $result = $this->attachWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Attach resource to the relation.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse|Response
     * @throws BindingResolutionException
     */
    protected function attachWithTransaction(Request $request, int|string $parentKey): JsonResponse|Response
    {
        $parentQuery = $this->buildAttachParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runAttachParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeAttach($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize($this->resolveAbility('update'), $parentEntity);

        $attachResult = $this->performAttach(
            $request,
            $parentEntity,
            $this->retrieve($request, 'resources'),
            $request->boolean('duplicates')
        );

        $afterHookResult = $this->afterAttach($request, $parentEntity, $attachResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json(
            [
                'attached' => Arr::get($attachResult, 'attached', []),
            ]
        );
    }

    /**
     * The hook is executed before attaching relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Response|null
     */
    protected function beforeAttach(Request $request, Model $parentEntity): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in attach method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildAttachParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in attach method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runAttachParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Attaches the given relation resources to the parent entity.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $resources
     * @param bool $duplicates
     * @return array
     * @throws BindingResolutionException
     */
    protected function performAttach(Request $request, Model $parentEntity, array $resources, bool $duplicates): array
    {
        $resources = $this->prepareResourcePivotFields($this->preparePivotResources($resources));

        if ($duplicates) {
            $parentEntity->{$this->relation()}()->attach($resources);
            return [
                'attached' => array_keys($resources),
            ];
        }

        return $parentEntity->{$this->relation()}()->sync($resources, false);
    }

    /**
     * Retrieves only fillable pivot fields and json encodes any objects/arrays.
     *
     * @param array $resources
     * @return array
     */
    protected function prepareResourcePivotFields(array $resources): array
    {
        foreach ($resources as $key => &$pivotFields) {
            if (!is_array($pivotFields)) {
                continue;
            }
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
    protected function preparePivotFields(array $pivotFields): array
    {
        foreach ($pivotFields as &$field) {
            if (is_array($field) || is_object($field)) {
                $field = json_encode($field);
            }
        }

        $pivotFields = Arr::only($pivotFields, $this->getPivotFillable());

        return $pivotFields;
    }

    /**
     * Standardizes resources array structure and authorizes individual resources.
     *
     * @param array $resources
     * @return array
     * @throws BindingResolutionException
     */
    protected function preparePivotResources(array $resources): array
    {
        $resources = $this->standardizePivotResourcesArray($resources);

        $model = $this->model();
        $relationInstance = (new $model)->{$this->relation()}();

        $resourceModelClass = $this->resolveResourceModelClass();
        $resourceModel = new $resourceModelClass();
        $resourceKeyName = $this->keyName();
        $resourceModels = $resourceModel->whereIn($resourceKeyName, array_keys($resources))->get();

        return $resourceModels->filter(function ($resourceModel) {
            return !$this->authorizationRequired() ||
                Gate::forUser($this->resolveUser())->allows('view', $resourceModel);
        })
            ->mapWithKeys(function ($resourceModel) use ($relationInstance, $resources, $resourceKeyName) {
                return [
                    $resourceModel->{$relationInstance->getRelatedKeyName(
                    )} => $resources[$resourceModel->{$resourceKeyName}],
                ];
            }
            )->all();
    }

    /**
     * Standardizes resources array structure.
     *
     * @param array $resources
     * @return array
     */
    protected function standardizePivotResourcesArray(array $resources): array
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
     * The hook is executed after attaching relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $attachResult
     * @return Response|null
     */
    protected function afterAttach(Request $request, Model $parentEntity, array &$attachResult): ?Response
    {
        return null;
    }

    /**
     * Detach resource to the relation in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function detach(Request $request, int|string $parentKey): JsonResponse|Response
    {
        try {
            $this->startTransaction();
            $result = $this->detachWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Detach resource to the relation.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse|Response
     * @throws BindingResolutionException
     */
    protected function detachWithTransaction(Request $request, int|string $parentKey): JsonResponse|Response
    {
        $parentQuery = $this->buildDetachParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runDetachParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeDetach($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize($this->resolveAbility('update'), $parentEntity);

        $detachResult = $this->performDetach(
            $request,
            $parentEntity,
            $this->retrieve($request, 'resources')
        );

        $afterHookResult = $this->afterDetach($request, $parentEntity, $detachResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json(
            [
                'detached' => $detachResult,
            ]
        );
    }

    /**
     * The hook is executed before detaching relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Response|null
     */
    protected function beforeDetach(Request $request, Model $parentEntity): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in detach method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildDetachParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in detach method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runDetachParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Detaches the given relation resources from the parent entity.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $resources
     * @return array
     * @throws BindingResolutionException
     */
    protected function performDetach(Request $request, Model $parentEntity, array $resources): array
    {
        $resources = $this->prepareResourcePivotFields($this->preparePivotResources($resources));

        $parentEntity->{$this->relation()}()->detach(array_keys($resources));

        return array_keys($resources);
    }

    /**
     * The hook is executed after detaching relation resource.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $detachResult
     * @return Response|null
     */
    protected function afterDetach(Request $request, Model $parentEntity, array &$detachResult): ?Response
    {
        return null;
    }

    /**
     * Sync relation resources in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function sync(Request $request, int|string $parentKey): JsonResponse|Response
    {
        try {
            $this->startTransaction();
            $result = $this->syncWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Sync relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse|Response
     * @throws BindingResolutionException
     */
    protected function syncWithTransaction(Request $request, int|string $parentKey): JsonResponse|Response
    {
        $parentQuery = $this->buildSyncParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runSyncParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeSync($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize($this->resolveAbility('update'), $parentEntity);

        $syncResult = $this->performSync(
            $request,
            $parentEntity,
            $this->retrieve($request, 'resources'),
            $request->get('detaching', true)
        );

        $afterHookResult = $this->afterSync($request, $parentEntity, $syncResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json($syncResult);
    }

    /**
     * The hook is executed before syncing relation resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Response|null
     */
    protected function beforeSync(Request $request, Model $parentEntity): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in sync method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildSyncParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in sync method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runSyncParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Sync the given relation resources on the parent entity.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $resources
     * @param bool $detaching
     * @return array
     * @throws BindingResolutionException
     */
    protected function performSync(Request $request, Model $parentEntity, array $resources, bool $detaching): array
    {
        $resources = $this->prepareResourcePivotFields($this->preparePivotResources($resources));

        $syncResult = $parentEntity->{$this->relation()}()->sync(
            $resources,
            $detaching
        );

        $syncResult['detached'] = array_values($syncResult['detached']);

        return $syncResult;
    }

    /**
     * The hook is executed after syncing relation resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $syncResult
     * @return Response|null
     */
    protected function afterSync(Request $request, Model $parentEntity, array &$syncResult): ?Response
    {
        return null;
    }

    /**
     * Toggle relation resources in a transaction-safe way.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function toggle(Request $request, int|string $parentKey): JsonResponse|Response
    {
        try {
            $this->startTransaction();
            $result = $this->toggleWithTransaction($request, $parentKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Toggle relation resources.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return JsonResponse|Response
     * @throws BindingResolutionException
     */
    protected function toggleWithTransaction(Request $request, int|string $parentKey): JsonResponse|Response
    {
        $parentQuery = $this->buildToggleParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runToggleParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeToggle($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $this->authorize($this->resolveAbility('update'), $parentEntity);

        $toggleResult = $this->performToggle(
            $request,
            $parentEntity,
            $this->retrieve($request, 'resources')
        );

        $afterHookResult = $this->afterToggle($request, $parentEntity, $toggleResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json($toggleResult);
    }

    /**
     * The hook is executed before toggling relation resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Response|null
     */
    protected function beforeToggle(Request $request, Model $parentEntity): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in toggle method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildToggleParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in toggle method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runToggleParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Toggles the given relation resources on the parent entity.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $resources
     * @return array
     * @throws BindingResolutionException
     */
    protected function performToggle(Request $request, Model $parentEntity, array $resources): array
    {
        $resources = $this->prepareResourcePivotFields($this->preparePivotResources($resources));

        return $parentEntity->{$this->relation()}()->toggle($resources);
    }

    /**
     * The hook is executed after toggling relation resources.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $toggleResult
     * @return Response|null
     */
    protected function afterToggle(Request $request, Model $parentEntity, array &$toggleResult): ?Response
    {
        return null;
    }

    /**
     * Update relation resource pivot in a transaction-safe wqy.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string $relatedKey
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function updatePivot(Request $request, int|string $parentKey, int|string $relatedKey): JsonResponse|Response
    {
        try {
            $this->startTransaction();
            $result = $this->updatePivotWithTransaction($request, $parentKey, $relatedKey);
            $this->commitTransaction();
            return $result;
        } catch (Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Update relation resource pivot.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @param int|string $relatedKey
     * @return JsonResponse|Response
     * @throws BindingResolutionException
     */
    protected function updatePivotWithTransaction(Request $request, int|string $parentKey, int|string $relatedKey): JsonResponse|Response
    {
        $parentQuery = $this->buildUpdatePivotParentFetchQuery($request, $parentKey);
        $parentEntity = $this->runUpdatePivotParentFetchQuery($request, $parentQuery, $parentKey);

        $beforeHookResult = $this->beforeUpdatePivot($request, $parentEntity);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $query = $this->buildShowFetchQuery($request, $parentEntity, []);
        $entity = $this->runShowFetchQuery($request, $query, $parentEntity, $relatedKey);

        $this->authorize($this->resolveAbility('update'), [$entity, $parentEntity]);

        $updateResult = $this->performUpdatePivot($request, $parentEntity, $relatedKey, $request->get('pivot', []));

        $afterHookResult = $this->afterUpdatePivot($request, $parentEntity, $updateResult);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        return response()->json(
            [
                'updated' => $updateResult,
            ]
        );
    }

    /**
     * The hook is executed before updating relation resource pivot.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @return Response|null
     */
    protected function beforeUpdatePivot(Request $request, Model $parentEntity): ?Response
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching parent entity in update pivot method.
     *
     * @param Request $request
     * @param int|string $parentKey
     * @return Builder
     */
    protected function buildUpdatePivotParentFetchQuery(Request $request, int|string $parentKey): Builder
    {
        return $this->buildParentFetchQuery($request, $parentKey);
    }

    /**
     * Runs the given query for fetching parent entity in update pivot method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $parentKey
     * @return Model
     */
    protected function runUpdatePivotParentFetchQuery(Request $request, Builder $query, int|string $parentKey): Model
    {
        return $this->runParentFetchQuery($request, $query, $parentKey);
    }

    /**
     * Updates relation resource pivot.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param int|string $relatedKey
     * @param array $pivot
     * @return array
     */
    protected function performUpdatePivot(Request $request, Model $parentEntity, int|string $relatedKey, array $pivot): array
    {
        $pivot = $this->preparePivotFields($pivot);

        $parentEntity->{$this->relation()}()->updateExistingPivot($relatedKey, $pivot);

        return [is_numeric($relatedKey) ? (int) $relatedKey : $relatedKey];
    }

    /**
     * The hook is executed after updating relation resource pivot.
     *
     * @param Request $request
     * @param Model $parentEntity
     * @param array $updateResult
     * @return Response|null
     */
    protected function afterUpdatePivot(Request $request, Model $parentEntity, array &$updateResult): ?Response
    {
        return null;
    }

    /**
     * Casts pivot json fields to array on the given entity.
     *
     * @param Model $entity
     * @return Model
     */
    protected function castPivotJsonFields(Model $entity): Model
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
}
