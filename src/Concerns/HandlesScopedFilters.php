<?php

declare(strict_types=1);

namespace Orion\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
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
     * @param Builder|Relation $query
     * @param Request $request
     * @param array $requestedFilters
     * @return array
     */
    public function resolveScopedFilters($query, Request $request, array $requestedFilters): array
    {
        $filters = $this->getScopedFilterDescriptors(collect($requestedFilters));

        $scopedFilters = [];
        $appliedFilters = [];

        $request->request->set('filters', []);

        if ($request->json()) {
            $request->json()->set('filters', []);
        }

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
                    (function ($model) use ($filterDescriptor) {
                        $value = Arr::first($model->getAttributes());

                        if ($mapCallback = Arr::get($filterDescriptor, 'mapCallback')) {
                            $value = $mapCallback($value, $filterDescriptor);
                        }

                        return ['value' => $value];
                    })
                )->unique('value')->values()->toArray(),
            ];

            if (Arr::has($filterDescriptor, 'value')) {
                $appliedFilters[] = $filterDescriptor;

                $this->getPrimaryQueryBuilder()->applyFiltersToQuery($query, $request, $appliedFilters);
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
        $filters = collect(array_keys($this->scopedFilters()))
            ->filter(function (string $field) use ($requestedFilters) {
                return !$requestedFilters->contains('field', $field);
            })->map(
                function (string $field) {
                    return array_merge(['field' => $field], $this->scopedFilters()[$field]);
                }
            )->values();

        return $requestedFilters->merge($filters)->filter(
            function (array $filterDescriptor) {
                return in_array($filterDescriptor['field'], $this->filterableBy(), true);
            }
        );
    }
}
