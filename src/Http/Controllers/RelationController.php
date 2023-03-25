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
use Orion\Contracts\ComponentsResolver;
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
     * @var string|null $policy
     */
    protected $parentPolicy;

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
     * @var ComponentsResolver $parentComponentsResolver
     */
    protected $parentComponentsResolver;

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

        $this->parentComponentsResolver = App::makeWith(
            ComponentsResolver::class,
            ['resourceModelClass' => $this->getModel(),]
        );

        parent::__construct();

        $this->relationQueryBuilder = App::makeWith(
            QueryBuilder::class,
            [
                'resourceModelClass' => $this->resolveResourceModelClass(),
                'paramsValidator' => $this->paramsValidator,
                'relationsResolver' => $this->relationsResolver,
                'searchBuilder' => $this->searchBuilder,
            ]
        );
    }

    protected function bindComponents(): void
    {
        parent::bindComponents();

        if ($parentPolicy = $this->getParentPolicy()) {
            $this->parentComponentsResolver->bindPolicyClass($parentPolicy);
        }
    }

    /**
     * Retrieves model related to resource.
     *
     * @return string
     */
    public function resolveResourceModelClass(): string
    {
        return get_class($this->resolveRelation()->getRelated());
    }

    /**
     * Retrieves relation method.
     *
     * @return Relation
     */
    public function resolveRelation(): Relation
    {
        $model = $this->getModel();

        return (new $model)->{$this->getRelation()}();
    }

    /**
     * Retrieves the query builder used to query the end-resource.
     *
     * @return QueryBuilder
     */
    public function getResourceQueryBuilder(): QueryBuilder
    {
        return $this->getRelationQueryBuilder();
    }

    /**
     * @return string
     */
    public function getRelation(): string
    {
        return $this->relation;
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
     * @return string|null
     */
    public function getParentPolicy(): ?string
    {
        return $this->parentPolicy;
    }

    /**
     * @param string $policy
     * @return $this
     */
    public function setParentPolicy(string $policy): self
    {
        $this->parentPolicy = $policy;

        return $this;
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
     * @return array
     */
    public function getPivotFillable(): array
    {
        return $this->pivotFillable;
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
    public function getPivotJson(): array
    {
        return $this->pivotJson;
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
     * @return QueryBuilder
     */
    public function getRelationQueryBuilder(): QueryBuilder
    {
        return $this->relationQueryBuilder;
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
     * @return ComponentsResolver
     */
    public function getParentComponentsResolver(): ComponentsResolver
    {
        return $this->parentComponentsResolver;
    }

    /**
     * @param ComponentsResolver $componentsResolver
     * @return $this
     */
    public function setParentComponentsResolver(ComponentsResolver $componentsResolver): self
    {
        $this->parentComponentsResolver = $componentsResolver;

        return $this;
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
     * A qualified name of a pivot field.
     *
     * @param string $field
     * @return string
     */
    protected function resolveQualifiedPivotFieldName(string $field): string
    {
        $modelClass = $this->getModel();

        return (new $modelClass)->{$this->getRelation()}()->qualifyPivotColumn($field);
    }
}
