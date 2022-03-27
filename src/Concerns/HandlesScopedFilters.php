<?php

declare(strict_types=1);

namespace Orion\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Orion\Http\Controllers\RelationController;
use Orion\Http\Requests\Request;

/**
 * @mixin RelationController
 */
trait HandlesScopedFilters
{
    /**
     * @param $query
     * @param Request $request
     * @param array $mappingCallbacks
     * @return array
     */
    public function resolveScopedFilters($query, Request $request, array $mappingCallbacks): array
    {
        $requestedFilters = $request->get('filters', []);

        $filters = $this->getScopedFilterDescriptors(collect($requestedFilters));

        $scopedFilters = [];
        $appliedFilters = [];

        foreach ($filters as $filterDescriptor) {
            $qualifiedField = $this->qualifyScopedFilterField($filterDescriptor['field']);

            $scopedFilters[$filterDescriptor['field']] = [
                'values' => collect(
                    $query
                        ->select([$qualifiedField])
                        ->groupBy($qualifiedField)
                        ->reorder()
                        ->getModels()
                )->map(
                    (function ($model) use ($filterDescriptor, $mappingCallbacks) {
                        $value = Arr::first($model->getAttributes());

                        if ($mappingCallback = Arr::get($mappingCallbacks, $filterDescriptor['field'])) {
                            $value = $mappingCallback($value, $filterDescriptor);
                        }

                        return ['value' => $value];
                    })
                )->unique('value')->values()->toArray(),
            ];

            if (Arr::has($filterDescriptor, 'value')) {
                $appliedFilters[] = $filterDescriptor;

                $this->getResourceQueryBuilder()->applyFiltersToQuery($query, $request, $appliedFilters);
            }
        }

        return $scopedFilters;
    }

    protected function qualifyScopedFilterField(string $field): string
    {
        if (!str_contains($field, '.')) {
            return $this->resolveQualifiedFieldName($field);
        }

        $relation = $this->relationsResolver->relationFromParamConstraint($field);
        $relationField = $this->relationsResolver->relationFieldFromParamConstraint($field);

        if ($relation === 'pivot') {
            return $this->resolveQualifiedPivotFieldName($relationField);
        }

        return $this->resolveQualifiedRelationFieldName($relation, $relationField);
    }

    protected function getScopedFilterDescriptors(Collection $requestedFilters): Collection
    {
        $filters = collect($this->scopedFilters())
            ->filter(function (string $field) use ($requestedFilters) {
                return !$requestedFilters->contains('field', $field);
            })->map(
                function (string $field) {
                    return ['field' => $field];
                }
            )->values();

        return $requestedFilters->merge($filters)->filter(
            function (array $filterDescriptor) {
                return in_array($filterDescriptor['field'], $this->filterableBy(), true);
            }
        );
    }
}
