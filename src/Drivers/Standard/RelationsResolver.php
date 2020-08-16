<?php

namespace Orion\Drivers\Standard;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Orion\Http\Requests\Request;

class RelationsResolver implements \Orion\Contracts\RelationsResolver
{
    /**
     * @var array
     */
    private $includableRelations;

    /**
     * @var array
     */
    private $alwaysIncludedRelations;

    /**
     * @inheritDoc
     */
    public function __construct(array $includableRelations, array $alwaysIncludedRelations)
    {
        $this->includableRelations = $includableRelations;
        $this->alwaysIncludedRelations = $alwaysIncludedRelations;
    }

    /**
     * Build the list of relations allowed to be included together with a resource based on the "include" query parameter.
     *
     * @param Request $request
     * @return array
     */
    public function requestedRelations(Request $request): array
    {
        $requestedIncludesStr = $request->get('include', '');
        $requestedIncludes = explode(',', $requestedIncludesStr);

        $allowedIncludes = array_unique(array_merge($this->includableRelations, $this->alwaysIncludedRelations));

        $validatedIncludes = array_filter($requestedIncludes, function ($include) use ($allowedIncludes) {
            return in_array($include, $allowedIncludes, true);
        });

        return array_unique(array_merge($validatedIncludes, $this->alwaysIncludedRelations));
    }

    /**
     * Resolves relation name from the given param constraint.
     *
     * @param string $paramConstraint
     * @return string
     */
    public function relationFromParamConstraint(string $paramConstraint): string
    {
        $paramConstraintParts = explode('.', $paramConstraint);
        if (count($paramConstraintParts) === 2) {
            return Arr::first($paramConstraintParts);
        }

        return implode('.', array_slice($paramConstraintParts, -1));
    }

    /**
     * Resolves relation field from the given param constraint.
     *
     * @param string $paramConstraint
     * @return string
     */
    public function relationFieldFromParamConstraint(string $paramConstraint): string
    {
        return Arr::last(explode('.', $paramConstraint));
    }

    /**
     * Resolved relation table name from the given relation instance.
     *
     * @param Relation $relationInstance
     * @return string
     */
    public function relationTableFromRelationInstance($relationInstance): string
    {
        return $relationInstance->getModel()->getTable();
    }

    /**
     * Resolves relation foreign key from the given relation instance.
     *
     * @param Relation $relationInstance
     * @return string
     */
    public function relationForeignKeyFromRelationInstance($relationInstance): string
    {
        $laravelVersion = (float) app()->version();

        return $laravelVersion > 5.7 || get_class($relationInstance) === HasOne::class ? $relationInstance->getQualifiedForeignKeyName() : $relationInstance->getQualifiedForeignKey();
    }

    /**
     * Resolves relation local key from the given relation instance.
     *
     * @param Relation $relationInstance
     * @return string
     */
    public function relationLocalKeyFromRelationInstance($relationInstance): string
    {
        switch (get_class($relationInstance)) {
            case HasOne::class:
            case MorphOne::class:
                return $relationInstance->getParent()->getTable().'.'.$relationInstance->getLocalKeyName();
                break;
            case BelongsTo::class:
            case MorphTo::class:
                return $relationInstance->getQualifiedOwnerKeyName();
                break;
            default:
                return $relationInstance->getQualifiedLocalKeyName();
                break;
        }
    }
}
