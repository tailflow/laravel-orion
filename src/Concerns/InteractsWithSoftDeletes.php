<?php

namespace Orion\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

trait InteractsWithSoftDeletes
{
    /**
     * Determine whether the given resource model uses soft deletes.
     *
     * @param string $resourceModelClass
     * @return bool
     */
    protected function softDeletes(string $resourceModelClass): bool
    {
        return method_exists(new $resourceModelClass, 'trashed');
    }

    /**
     * Determines, if the current request forces deletion of a resource.
     *
     * @param Request $request
     * @param bool $softDeletes
     * @return bool
     */
    protected function forceDeletes(Request $request, bool $softDeletes): bool
    {
        return $softDeletes && $request->get('force');
    }

    /**
     * Determines, if the resource is considered trashed for the current request.
     *
     * @param Model|SoftDeletes $entity
     * @param bool $softDeletes
     * @param bool $forceDeletes
     * @return bool
     */
    protected function isResourceTrashed(Model $entity, bool $softDeletes, bool $forceDeletes): bool
    {
        return !$forceDeletes && $softDeletes && $entity->trashed();
    }
}
