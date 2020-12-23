<?php

namespace Orion\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Orion\Http\Requests\Request;

interface RelationsResolver
{
    public function __construct(array $includableRelations, array $alwaysIncludedRelations);

    public function requestedRelations(Request $request): array;

    public function relationFromParamConstraint(string $paramConstraint): string;

    public function relationFieldFromParamConstraint(string $paramConstraint): string;

    public function relationTableFromRelationInstance(Relation $relationInstance): string;

    public function relationForeignKeyFromRelationInstance(Relation $relationInstance): string;

    public function relationLocalKeyFromRelationInstance(Relation $relationInstance): string;

    public function guardRelationsForCollection(Collection $entities, array $requestedRelations, bool $normalized = false): Collection;

    public function guardRelations(Model $entity, array $requestedRelations, bool $normalized = false);
}
