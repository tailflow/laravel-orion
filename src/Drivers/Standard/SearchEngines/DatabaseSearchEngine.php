<?php

declare(strict_types=1);

namespace Orion\Drivers\Standard\SearchEngines;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Orion\Contracts\RelationsResolver;
use Orion\Contracts\SearchEngine;
use Orion\Http\Requests\Request;

class DatabaseSearchEngine implements SearchEngine
{
    /** @var array */
    protected $searchableBy;
    /** @var string */
    protected $resourceModelClass;
    /** @var RelationsResolver */
    protected $relationsResolver;

    public function __construct(array $searchableBy, string $resourceModelClass, RelationsResolver $relationsResolver)
    {
        $this->searchableBy = $searchableBy;
        $this->resourceModelClass = $resourceModelClass;
        $this->relationsResolver = $relationsResolver;
    }

    public function searchableBy(): array
    {
        return $this->searchableBy;
    }

    /**
     * @param Builder|Relation $query
     * @param array $descriptor
     * @param Request $request
     * @return mixed
     */
    public function applyScopeConstraint($query, array $descriptor, Request $request)
    {
        return $query->{$descriptor['name']}(...Arr::get($descriptor, 'parameters', []));
    }

    /**
     * @param Builder|Relation $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyFieldConstraint($query, string $field, array $descriptor, bool $or, Request $request)
    {
        return $this->applyUnqualifiedFieldConstraint(
            $query,
            $this->getQualifiedFieldName($field),
            $descriptor,
            $or,
            $request
        );
    }

    /**
     * @param Builder|Relation $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    protected function applyUnqualifiedFieldConstraint(
        $query,
        string $field,
        array $descriptor,
        bool $or,
        Request $request
    ) {
        return $query->{$or ? 'orWhere' : 'where'}(
            $field,
            $descriptor['operator'],
            $descriptor['value']
        );
    }

    /**
     * @param Builder|Relation $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyFieldInConstraint($query, string $field, array $descriptor, bool $or, Request $request)
    {
        return $this->applyUnqualifiedFieldInConstraint(
            $query,
            $this->getQualifiedFieldName($field),
            $descriptor,
            $or,
            $request
        );
    }

    /**
     * @param Builder|Relation $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    protected function applyUnqualifiedFieldInConstraint(
        $query,
        string $field,
        array $descriptor,
        bool $or,
        Request $request
    ) {
        return $query->{$or ? 'orWhereIn' : 'whereIn'}(
            $field,
            $descriptor['value'],
            'and',
            $descriptor['operator'] === 'not in'
        );
    }

    /**
     * @param Builder|Relation $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyFieldNullConstraint($query, string $field, array $descriptor, bool $or, Request $request)
    {
        return $this->applyUnqualifiedFieldNullConstraint(
            $query,
            $this->getQualifiedFieldName($field),
            $descriptor,
            $or,
            $request
        );
    }

    /**
     * @param Builder|Relation $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    protected function applyUnqualifiedFieldNullConstraint(
        $query,
        string $field,
        array $descriptor,
        bool $or,
        Request $request
    ) {
        return $query->{$or ? 'orWhereNull' : 'whereNull'}($field);
    }

    /**
     * @param Builder|Relation $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyFieldDateConstraint($query, string $field, array $descriptor, bool $or, Request $request)
    {
        return $this->applyUnqualifiedFieldDateConstraint(
            $query,
            $this->getQualifiedFieldName($field),
            $descriptor,
            $or,
            $request
        );
    }

    /**
     * @param Builder|Relation $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    protected function applyUnqualifiedFieldDateConstraint(
        $query,
        string $field,
        array $descriptor,
        bool $or,
        Request $request
    ) {
        return $query->{$or ? 'orWhereDate' : 'whereDate'}(
            $field,
            $descriptor['operator'],
            $descriptor['value']
        );
    }

    /**
     * @param Relation $query
     * @param string $relation
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyRelationFieldConstraint(
        $query,
        string $relation,
        string $field,
        array $descriptor,
        bool $or,
        Request $request
    ) {
        return $query->{$or ? 'orWhereHas' : 'whereHas'}(
            $relation,
            function ($relationQuery) use ($field, $descriptor, $request) {
                return $this->applyUnqualifiedFieldConstraint($relationQuery, $field, $descriptor, false, $request);
            }
        );
    }

    /**
     * @param Relation $query
     * @param string $relation
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyRelationFieldInConstraint(
        $query,
        string $relation,
        string $field,
        array $descriptor,
        bool $or,
        Request $request
    ) {
        return $query->{$or ? 'orWhereHas' : 'whereHas'}(
            $relation,
            function ($relationQuery) use ($field, $descriptor, $request) {
                return $this->applyUnqualifiedFieldInConstraint($relationQuery, $field, $descriptor, false, $request);
            }
        );
    }

    /**
     * @param Relation $query
     * @param string $relation
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyRelationFieldNullConstraint(
        $query,
        string $relation,
        string $field,
        array $descriptor,
        bool $or,
        Request $request
    ) {
        return $query->{$or ? 'orWhereHas' : 'whereHas'}(
            $relation,
            function ($relationQuery) use ($field, $descriptor, $request) {
                return $this->applyUnqualifiedFieldNullConstraint($relationQuery, $field, $descriptor, false, $request);
            }
        );
    }

    /**
     * @param Relation $query
     * @param string $relation
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyRelationFieldDateConstraint(
        $query,
        string $relation,
        string $field,
        array $descriptor,
        bool $or,
        Request $request
    ) {
        return $query->{$or ? 'orWhereHas' : 'whereHas'}(
            $relation,
            function ($relationQuery) use ($field, $descriptor, $request) {
                return $this->applyUnqualifiedFieldDateConstraint($relationQuery, $field, $descriptor, false, $request);
            }
        );
    }

    /**
     * @param BelongsToMany $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyPivotFieldConstraint($query, string $field, array $descriptor, bool $or, Request $request)
    {
        return $query->{$or ? 'orWherePivot' : 'wherePivot'}(
            $field,
            $descriptor['operator'],
            $descriptor['value']
        );
    }

    /**
     * @param BelongsToMany $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyPivotFieldInConstraint($query, string $field, array $descriptor, bool $or, Request $request)
    {
        return $query->{$or ? 'orWherePivotIn' : 'wherePivotIn'}(
            $field,
            $descriptor['value'],
            'and',
            $descriptor['operator'] === 'not in'
        );
    }

    /**
     * @param BelongsToMany $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyPivotFieldNullConstraint($query, string $field, array $descriptor, bool $or, Request $request)
    {
        if ((float) app()->version() <= 7.0) {
            return $query->addNestedWhereQuery(
                $query->newPivotStatement()->{$or ? 'orWhereNull' : 'whereNull'}(
                    $query->getTable() . ".{$field}",
                )
            );
        }

        return $query->{$or ? 'orWherePivotNull' : 'wherePivotNull'}($field);
    }

    /**
     * @param BelongsToMany $query
     * @param string $field
     * @param array $descriptor
     * @param bool $or
     * @param Request $request
     * @return mixed
     */
    public function applyPivotFieldDateConstraint($query, string $field, array $descriptor, bool $or, Request $request)
    {
        return $query->addNestedWhereQuery(
            $query->newPivotStatement()->whereDate(
                $query->getTable() . ".{$field}",
                $descriptor['operator'],
                $descriptor['value']
            )
        );
    }

    /**
     * @param Builder|Relation $query
     * @param string $field
     * @param bool $caseSensitive
     * @param array $descriptor
     * @param Request $request
     * @return mixed
     */
    public function applyFieldSearchConstraint(
        $query,
        string $field,
        bool $caseSensitive,
        array $descriptor,
        Request $request
    ) {
        $qualifiedFieldName = $this->getQualifiedFieldName($field);

        if (!$caseSensitive) {
            return $query->orWhereRaw("lower({$qualifiedFieldName}) like lower(?)", ['%' . $descriptor['value'] . '%']);
        }

        return $query->orWhere($qualifiedFieldName, 'like', '%' . $descriptor['value'] . '%');
    }

    /**
     * @param Relation $query
     * @param string $relation
     * @param string $field
     * @param bool $caseSensitive
     * @param array $descriptor
     * @param Request $request
     * @return mixed
     */
    public function applyRelationFieldSearchConstraint(
        $query,
        string $relation,
        string $field,
        bool $caseSensitive,
        array $descriptor,
        Request $request
    ) {
        return $query->orWhereHas(
            $relation,
            function ($relationQuery) use ($field, $descriptor, $caseSensitive) {
                /**
                 * @var Builder $relationQuery
                 */
                if (!$caseSensitive) {
                    return $relationQuery->whereRaw(
                        "lower({$field}) like lower(?)",
                        ['%' . $descriptor['value'] . '%']
                    );
                }

                return $relationQuery->where($field, 'like', '%' . $descriptor['value'] . '%');
            }
        );
    }

    /**
     * @param BelongsToMany $query
     * @param string $field
     * @param bool $caseSensitive
     * @param array $descriptor
     * @param Request $request
     * @return mixed
     */
    public function applyPivotFieldSearchConstraint(
        $query,
        string $field,
        bool $caseSensitive,
        array $descriptor,
        Request $request
    ) {
        if (!$caseSensitive) {
            $query->addNestedWhereQuery(
                $query->newPivotStatement()->whereRaw(
                    "lower({$query->getTable()}.{$field}) like lower(?)",
                    ['%' . $descriptor['value'] . '%']
                )
            );
        } else {
            $query->wherePivot($field, 'like', '%' . $descriptor['value'] . '%');
        }

        return $query->select($this->getQualifiedFieldName('*'));
    }

    /**
     * @param Builder|Relation $query
     * @param string $direction
     * @param array $descriptor
     * @param Request $request
     * @return mixed
     */
    public function applyFieldSorting($query, string $direction, array $descriptor, Request $request)
    {
        return $query->orderBy($this->getQualifiedFieldName($descriptor['field']), $direction);
    }

    /**
     * @param Relation $query
     * @param string $relation
     * @param string $field
     * @param string $direction
     * @param array $descriptor
     * @param Request $request
     * @return mixed
     */
    public function applyRelationFieldSorting(
        $query,
        string $relation,
        string $field,
        string $direction,
        array $descriptor,
        Request $request
    ) {
        $relationInstance = (new $this->resourceModelClass)->{$relation}();

        $relationTable = $this->relationsResolver->relationTableFromRelationInstance($relationInstance);
        $relationForeignKey = $this->relationsResolver->relationForeignKeyFromRelationInstance(
            $relationInstance
        );
        $relationLocalKey = $this->relationsResolver->relationLocalKeyFromRelationInstance($relationInstance);

        return $query->leftJoin($relationTable, $relationForeignKey, '=', $relationLocalKey)
            ->orderBy("$relationTable.$field", $direction)
            ->select($this->getQualifiedFieldName('*'));
    }

    /**
     * @param BelongsToMany $query
     * @param string $field
     * @param string $direction
     * @param array $descriptor
     * @param Request $request
     * @return mixed
     */
    public function applyPivotFieldSorting(
        $query,
        string $field,
        string $direction,
        array $descriptor,
        Request $request
    ) {
        return $query->orderByPivot($field, $direction);
    }

    /**
     * @param Builder|Relation|SoftDeletes $query
     * @param Request $request
     * @return mixed
     */
    public function applySoftDeletesWithTrashedConstraint($query, Request $request)
    {
        return $query->withTrashed();
    }

    /**
     * @param Builder|Relation|SoftDeletes $query
     * @param Request $request
     * @return mixed
     */
    public function applySoftDeletesOnlyTrashedConstraint($query, Request $request)
    {
        return $query->onlyTrashed();
    }

    /**
     * Builds a complete field name with table.
     *
     * @param string $field
     * @return string
     */
    public function getQualifiedFieldName(string $field): string
    {
        $table = (new $this->resourceModelClass)->getTable();
        return "{$table}.{$field}";
    }
}
