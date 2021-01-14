<?php

namespace Orion\Drivers\Standard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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

        $validatedIncludes = [];

        foreach ($requestedIncludes as $requestedInclude) {
            if (in_array($requestedInclude, $allowedIncludes, true)) {
                $validatedIncludes[] = $requestedInclude;
            }

            if (strpos($requestedInclude, '.') !== false) {
                $relations = explode('.', $requestedInclude);
                $relationMatcher = '';

                foreach ($relations as $relation) {
                    $relationMatcher .= "{$relation}.";

                    if (in_array("{$relationMatcher}*", $allowedIncludes, true)) {
                        $validatedIncludes[] = $requestedInclude;
                    }
                }
            }
        }

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
    public function relationTableFromRelationInstance(Relation $relationInstance): string
    {
        return $relationInstance->getModel()->getTable();
    }

    /**
     * Resolves relation foreign key from the given relation instance.
     *
     * @param Relation $relationInstance
     * @return string
     */
    public function relationForeignKeyFromRelationInstance(Relation $relationInstance): string
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
    public function relationLocalKeyFromRelationInstance(Relation $relationInstance): string
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

    /**
     * Removes loaded relations that were not requested and exposed on the given collection of entities.
     *
     * @param Collection $entities
     * @param array $requestedRelations
     * @param bool $normalized
     * @return Collection
     */
    public function guardRelationsForCollection(Collection $entities, array $requestedRelations, bool $normalized = false): Collection
    {
        return $entities->transform(function ($entity) use ($requestedRelations, $normalized) {
            return $this->guardRelations($entity, $requestedRelations, $normalized);
        });
    }

    /**
     * Removes loaded relations that were not requested and exposed on the given entity.
     *
     * @param Model $entity
     * @param array $requestedRelations
     * @param bool $normalized
     * @return Model
     */
    public function guardRelations(Model $entity, array $requestedRelations, bool $normalized = false): Model
    {
        if (!$normalized) {
            $requestedRelations = $this->normalizeRequestedRelations($requestedRelations);
        }

        $relations = $entity->getRelations();
        ksort($relations);

        foreach ($relations as $relationName => $relation) {
            if ($relationName === 'pivot') {
                continue;
            }

            if (!array_key_exists($relationName, $requestedRelations)) {
                unset($relations[$relationName]);
            } elseif ($relation !== null) {
                if ($relation instanceof Model) {
                    $relation = $this->guardRelations($relation, $requestedRelations[$relationName], true);
                } else {
                    $relation = $this->guardRelationsForCollection($relation, $requestedRelations[$relationName], true);
                }
            }
        }

        $entity->setRelations($relations);

        return $entity;
    }

    protected function normalizeRequestedRelations(array $requestedRelations): array
    {
        $normalizedRelations = [];

        foreach ($requestedRelations as $requestedRelation) {
            if (($firstDotIndex = strpos($requestedRelation, '.')) !== false) {
                $parentOfNestedRelation = Arr::first(explode('.', $requestedRelation));
                $nestedRelation = substr($requestedRelation, $firstDotIndex + 1);

                $normalizedNestedRelations = $this->normalizeRequestedRelations([$nestedRelation]);

                $normalizedRelations[$parentOfNestedRelation] = array_merge_recursive(
                    Arr::get($normalizedRelations, $parentOfNestedRelation, []),
                    $normalizedNestedRelations
                );
            } elseif (!array_key_exists($requestedRelation, $normalizedRelations)) {
                $normalizedRelations[$requestedRelation] = [];
            }
        }

        return $normalizedRelations;
    }
}
