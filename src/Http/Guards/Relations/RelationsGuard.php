<?php

declare(strict_types=1);

namespace Orion\Http\Guards\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Orion\Contracts\Http\Guards\Guard;
use Orion\Contracts\Http\Guards\GuardOptions;

class RelationsGuard implements Guard
{
    /**
     * @param Model $entity
     * @param RelationsGuardOptions $options
     * @return Model
     */
    public function guardEntity(Model $entity, GuardOptions $options): Model
    {
        if (!$options->normalized) {
            $options->requestedRelations = $this->normalizeRequestedRelations($options->requestedRelations);
        }

        $relations = $entity->getRelations();
        ksort($relations);

        foreach ($relations as $relationName => $relation) {
            if ($relationName === 'pivot' || $relationName === $options->parentRelation) {
                continue;
            }

            if (!array_key_exists($relationName, $options->requestedRelations)) {
                unset($relations[$relationName]);
            } elseif ($relation !== null) {
                $relationOptions = clone $options;
                $relationOptions->requestedRelations = $options->requestedRelations[$relationName];
                $relationOptions->parentRelation = $relationName;
                $relationOptions->normalized = true;

                if ($relation instanceof Model) {
                    $relation = $this->guardEntity(
                        $relation,
                        $relationOptions
                    );
                } else {
                    $relation = $this->guardCollection(
                        $relation,
                        $relationOptions
                    );
                }
            }
        }

        $entity->setRelations($relations);

        return $entity;
    }

    /**
     * @param Collection $collection
     * @param RelationsGuardOptions $options
     * @return Collection
     */
    public function guardCollection(Collection $collection, GuardOptions $options): Collection
    {
        return $collection->transform(
            function ($entity) use ($options) {
                return $this->guardEntity($entity, $options);
            }
        );
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
