<?php

namespace Orion\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Orion\Concerns\HandlesRelationManyToManyOperations;
use Orion\Concerns\HandlesRelationOneToManyOperations;
use Orion\Concerns\HandlesRelationStandardBatchOperations;
use Orion\Concerns\HandlesRelationStandardOperations;
use Orion\Contracts\QueryBuilder;
use Orion\Exceptions\BindingException;

abstract class RelationController extends BaseController
{
    use HandlesRelationStandardOperations, HandlesRelationStandardBatchOperations, HandlesRelationOneToManyOperations, HandlesRelationManyToManyOperations;

    /**
     * @var string $relation
     */
    protected $relation;

    /**
     * The list of pivot fields that can be set upon relation resource creation or update.
     *
     * @var array
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
        if (!$this->relation) {
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
        $model = $this->getModel();

        return get_class((new $model)->{$this->getRelation()}()->getRelated());
    }

    /**
     * The name of the field used to fetch parent resource from the database.
     *
     * @return string
     */
    protected function parentKeyName(): string
    {
        $modelClass = $this->getModel();

        return (new $modelClass)->getKeyName();
    }

    /**
     * A qualified name of the field used to fetch parent resource from the database.
     *
     * @return string
     */
    protected function resolveQualifiedParentKeyName(): string
    {
        $modelClass = $this->getModel();

        return (new $modelClass)->qualifyColumn($this->parentKeyName());
    }

    /**
     * Creates new Eloquent query builder of the relation on the given parent model.
     *
     * @param Model $parentEntity
     * @return Builder|Relation
     */
    public function newRelationQuery(Model $parentEntity)
    {
        return $parentEntity->{$this->getRelation()}();
    }

    /**
     * @param string $relation
     * @return $this
     */
    public function setRelation(string $relation): self
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelation(): string
    {
        return $this->relation;
    }

    /**
     * @param array $pivotFillable
     * @return $this
     */
    public function setPivotFillable(array $pivotFillable): self
    {
        $this->pivotFillable = $pivotFillable;

        return $this;
    }

    /**
     * @return array
     */
    public function getPivotFillable(): array
    {
        return $this->pivotFillable;
    }

    /**
     * @param array $pivotJson
     * @return $this
     */
    public function setPivotJson(array $pivotJson): self
    {
        $this->pivotJson = $pivotJson;

        return $this;
    }

    /**
     * @return array
     */
    public function getPivotJson(): array
    {
        return $this->pivotJson;
    }

    /**
     * @param QueryBuilder $relationQueryBuilder
     * @return $this
     */
    public function setRelationQueryBuilder(QueryBuilder $relationQueryBuilder): self
    {
        $this->relationQueryBuilder = $relationQueryBuilder;

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function getRelationQueryBuilder(): QueryBuilder
    {
        return $this->relationQueryBuilder;
    }
}
