<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class RelationOperationBuilder extends OperationBuilder
{
    /**
     * @param bool $pluralize
     * @return string
     * @throws BindingResolutionException
     */
    protected function resolveParentResourceName(bool $pluralize = false): string
    {
        $parentResourceModelClass = app()->make($this->resource->controller)->getModel();
        /** @var Model $parentResourceModel */
        $parentResourceModel = app()->make($parentResourceModelClass);

        $resourceName = Str::lower(str_replace('_', ' ', $parentResourceModel->getTable()));

        if (!$pluralize) {
            return Str::singular($resourceName);
        }

        return $resourceName;
    }
}
