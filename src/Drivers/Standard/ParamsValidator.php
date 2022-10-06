<?php

namespace Orion\Drivers\Standard;

use Illuminate\Support\Facades\Validator;
use Orion\Http\Requests\Request;
use Orion\Http\Rules\WhitelistedField;

class ParamsValidator implements \Orion\Contracts\ParamsValidator
{
    /**
     * @var string[]
     */
    private $exposedScopes;

    /**
     * @var string[]
     */
    private $filterableBy;

    /**
     * @var string[]
     */
    private $sortableBy;

    /**
     * @var string[]
     */
    private $aggregatableBy;

    /**
     * @inheritDoc
     */
    public function __construct(array $exposedScopes = [], array $filterableBy = [], array $sortableBy = [], array $aggregatableBy = [])
    {
        $this->exposedScopes = $exposedScopes;
        $this->filterableBy = $filterableBy;
        $this->sortableBy = $sortableBy;
        $this->aggregatableBy = $aggregatableBy;
    }

    public function validateScopes(Request $request): void
    {
        Validator::make(
            $request->all(),
            [
                'scopes' => ['sometimes', 'array'],
                'scopes.*.name' => ['required_with:scopes', 'in:'.implode(',', $this->exposedScopes)],
                'scopes.*.parameters' => ['sometimes', 'array'],
            ]
        )->validate();
    }

    public function validateFilters(Request $request): void
    {
        $maxDepth = floor($this->getArrayDepth($request->input('filters', [])) / 2);
        $configMaxNestedDepth = config('orion.search.max_nested_depth', 1);

        abort_if(
            $maxDepth > $configMaxNestedDepth,
            422,
            __('Max nested depth :depth is exceeded', ['depth' => $configMaxNestedDepth])
        );

        Validator::make(
            $request->all(),
            array_merge([
                'filters' => ['sometimes', 'array'],
            ], $this->getNestedRules('filters', $maxDepth))
        )->validate();
    }

    /**
     * @param string $prefix
     * @param int $maxDepth
     * @param array $rules
     * @param int $currentDepth
     * @return array
     */
    protected function getNestedRules(string $prefix, int $maxDepth, array $rules = [], int $currentDepth = 1): array
    {
        $rules = array_merge($rules, [
            $prefix.'.*.type' => ['sometimes', 'in:and,or'],
            $prefix.'.*.field' => [
                "required_without:{$prefix}.*.nested",
                'regex:/^[\w.\_\-\>]+$/',
                new WhitelistedField($this->filterableBy),
            ],
            $prefix.'.*.operator' => [
                'sometimes',
                'in:<,<=,>,>=,=,!=,like,not like,ilike,not ilike,in,not in,all in,any in',
            ],
            $prefix.'.*.value' => ['nullable'],
            $prefix.'.*.nested' => ['sometimes', 'array',],
        ]);

        if ($maxDepth >= $currentDepth) {
            $rules = array_merge(
                $rules,
                $this->getNestedRules("{$prefix}.*.nested", $maxDepth, $rules, ++$currentDepth)
            );
        }

        return $rules;
    }

    protected function getArrayDepth($array): int
    {
        $maxDepth = 0;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->getArrayDepth($value) + 1;

                $maxDepth = max($depth, $maxDepth);
            }
        }

        return $maxDepth;
    }

    public function validateSort(Request $request): void
    {
        Validator::make(
            $request->all(),
            [
                'sort' => ['sometimes', 'array'],
                'sort.*.field' => [
                    'required_with:sort',
                    'regex:/^[\w.\_\-\>]+$/',
                    new WhitelistedField($this->sortableBy),
                ],
                'sort.*.direction' => ['sometimes', 'in:asc,desc'],
            ]
        )->validate();
    }

    public function validateSearch(Request $request): void
    {
        Validator::make(
            $request->all(),
            [
                'search' => ['sometimes', 'array'],
                'search.value' => ['string', 'nullable'],
                'search.case_sensitive' => ['bool'],
            ]
        )->validate();
    }

    public function validateAggregators(Request $request): void
    {
        Validator::make(
            $request->all(),
            [
                'aggregates' => ['sometimes', 'array'],
                'aggregates.count' => ['sometimes', 'array'],
                'aggregates.count.*.field' => [
                    'required',
                    'regex:/^[\w.\_\-\>]+$/',
                    new WhitelistedField($this->aggregatableBy),
                ],
            ]
        )->validate();
    }
}
