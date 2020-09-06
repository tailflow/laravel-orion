<?php

namespace Orion\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Orion\Http\Requests\Request;

interface RelationsResolver
{
    public function __construct(array $includableRelations, array $alwaysIncludedRelations);

    public function requestedRelations(Request $request): array;

    public function relationFromParamConstraint(string $paramConstraint): string;

    public function relationFieldFromParamConstraint(string $paramConstraint): string;

    public function relationTableFromRelationInstance($relationInstance): string;

    public function relationForeignKeyFromRelationInstance($relationInstance): string;

    public function relationLocalKeyFromRelationInstance($relationInstance): string;

    public function guardRelationsForCollection(Collection $entities, array $requestedRelations): Collection;

    public function guardRelations(Model $entity, array $requestedRelations);
}
