<?php

namespace Laralord\Orion\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laralord\Orion\Http\Requests\Request;
use Laralord\Orion\Traits\HandlesRelationCRUDOperations;
use Laralord\Orion\Traits\HandlesRelationManyToManyOperations;
use Laralord\Orion\Traits\HandlesRelationOneToManyOperations;

class RelationController extends BaseController
{
    use HandlesRelationCRUDOperations, HandlesRelationOneToManyOperations, HandlesRelationManyToManyOperations;

    /**
     * @var string|null $relation
     */
    protected static $relation = null;

    /**
     * @var string|null $relation
     */
    protected static $associatingRelation = null;

    /**
     * The list of pivot fields that can be set upon relation resource creation or update.
     *
     * @var bool
     */
    protected $pivotFillable = [];

    /**
     * The list of pivot json fields that needs to be casted to array.
     *
     * @var array
     */
    protected $pivotJson = [];

    /**
     * Retrieves model related to resource.
     *
     * @return string
     */
    protected function getResourceModel()
    {
        return get_class((new static::$model)->{static::$relation}()->getRelated());
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
}
