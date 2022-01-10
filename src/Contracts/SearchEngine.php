<?php

namespace Orion\Contracts;

use Orion\Http\Requests\Request;

interface SearchEngine
{
    public function __construct(array $searchableBy, string $resourceModelClass, RelationsResolver $relationsResolver);

    public function searchableBy(): array;

    /** Scopes */
    public function applyScopeConstraint($query, array $descriptor, Request $request);

    /** Filters */
    public function applyFieldConstraint($query, string $field, array $descriptor, bool $or, Request $request);
    public function applyFieldInConstraint($query, string $field, array $descriptor, bool $or, Request $request);
    public function applyFieldNullConstraint($query, string $field, array $descriptor, bool $or, Request $request);
    public function applyFieldDateConstraint($query, string $field, array $descriptor, bool $or, Request $request);
    public function applyRelationFieldConstraint($query, string $relation, string $field, array $descriptor, bool $or, Request $request);
    public function applyRelationFieldInConstraint($query, string $relation, string $field, array $descriptor, bool $or, Request $request);
    public function applyRelationFieldNullConstraint($query, string $relation, string $field,array $descriptor, bool $or, Request $request);
    public function applyRelationFieldDateConstraint($query, string $relation, string $field, array $descriptor, bool $or, Request $request);
    public function applyPivotFieldConstraint($query, string $field, array $descriptor, bool $or, Request $request);
    public function applyPivotFieldInConstraint($query, string $field, array $descriptor, bool $or, Request $request);
    public function applyPivotFieldNullConstraint($query, string $field, array $descriptor, bool $or, Request $request);
    public function applyPivotFieldDateConstraint($query, string $field, array $descriptor, bool $or, Request $request);

    /** Keyword & Full-Text Search */
    public function applyFieldSearchConstraint($query, string $field, bool $caseSensitive, array $descriptor, Request $request);
    public function applyRelationFieldSearchConstraint($query, string $relation, string $field, bool $caseSensitive, array $descriptor, Request $request);
    public function applyPivotFieldSearchConstraint($query, string $field, bool $caseSensitive, array $descriptor, Request $request);

    /** Sorting */
    public function applyFieldSorting($query, string $direction, array $descriptor, Request $request);
    public function applyRelationFieldSorting($query, string $relation, string $field, string $direction, array $descriptor, Request $request);
    public function applyPivotFieldSorting($query, string $field, string $direction, array $descriptor, Request $request);

    /** Soft Deletes */
    public function applySoftDeletesWithTrashedConstraint($query, Request $request);
    public function applySoftDeletesOnlyTrashedConstraint($query, Request $request);
}
