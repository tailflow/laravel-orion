<?php

namespace Orion\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Orion\Concerns\HandlesRelationManyToManyOperations;
use Orion\Concerns\HandlesRelationOneToManyOperations;
use Orion\Concerns\HandlesRelationStandardOperations;
use Orion\Contracts\QueryBuilder;
use Orion\Exceptions\BindingException;

class RelationController extends BaseController
{
    use HandlesRelationStandardOperations, HandlesRelationOneToManyOperations, HandlesRelationManyToManyOperations;

    /**
     * @var string $relation
     */
    protected static $relation;

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
     * @var QueryBuilder $relationQueryBuilder
     */
    protected $relationQueryBuilder;

    /**
     * RelationController constructor.
     *
     * @throws BindingException
     */
    public function __construct()
    {
        if (!static::$relation) {
            throw new BindingException('Relation is not defined for '.static::class);
        }

        parent::__construct();

        $this->relationQueryBuilder = App::makeWith(QueryBuilder::class, [
            'resourceModelClass' => $this->resolveResourceModelClass(),
            'paramsValidator' => $this->paramsValidator,
            'relationsResolver' => $this->relationsResolver,
            'searchBuilder' => $this->searchBuilder
        ]);
    }

    /**
     * Retrieves model related to resource.
     *
     * @return string
     */
    public function resolveResourceModelClass(): string
    {
        return get_class((new static::$model)->{static::$relation}()->getRelated());
    }

    /**
     * Creates new Eloquent query builder of the relation on the given parent model.
     *
     * @param Model $parentEntity
     * @return Builder
     */
    public function newRelationQuery(Model $parentEntity): Builder
    {
        return $parentEntity->{static::$relation}()->getQuery();
    }
}
