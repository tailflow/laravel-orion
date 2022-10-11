<?php

namespace Orion\Drivers\Standard;

use Illuminate\Support\Facades\Validator;
use Orion\Exceptions\MaxNestedDepthExceededException;
use Orion\Helper\ArrayHelper;
use Orion\Http\Requests\Request;
use Orion\Http\Rules\WhitelistedField;
use Orion\Http\Rules\WhitelistedQueryFields;

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
     * @var string[]
     */
    private $aggregatesFilterableBy;

    /**
     * @var string[]
     */
    private $includableBy;

    /**
     * @var string[]
     */
    private $includesFilterableBy;

    /**
     * @inheritDoc
     */
    public function __construct(array $exposedScopes = [], array $filterableBy = [], array $sortableBy = [], array $aggregatableBy = [], array $aggregatesFilterableBy = [], array $includableBy = [], array $includesFilterableBy = [])
    {
        $this->exposedScopes = $exposedScopes;
        $this->filterableBy = $filterableBy;
        $this->sortableBy = $sortableBy;
        $this->aggregatableBy = $aggregatableBy;
        $this->aggregatesFilterableBy = $aggregatesFilterableBy;
        $this->includableBy = $includableBy;
        $this->includesFilterableBy = $includesFilterableBy;
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
        $depth = $this->nestedFiltersDepth($request->input('filters', []));

        Validator::make(
            $request->all(),
            array_merge([
                'filters' => ['sometimes', 'array'],
            ], $this->getNestedRules('filters', $depth, $this->filterableBy))
        )->validate();
    }

    /**
     * @throws MaxNestedDepthExceededException
     */
    protected function nestedFiltersDepth($array, $modifier = 0) {
        $depth = ArrayHelper::depth($array);
        $configMaxNestedDepth = config('orion.search.max_nested_depth', 1);

        // Here we calculate the real nested filters depth
        $depth = floor($depth / 2);

        if ($depth + $modifier > $configMaxNestedDepth) {
            throw new MaxNestedDepthExceededException(422, __('Max nested depth :depth is exceeded', ['depth' => $configMaxNestedDepth]));
        }

        return $depth;
    }

    /**
     * @param string $prefix
     * @param int $maxDepth
     * @param array $filterableBy
     * @param array $rules
     * @param int $currentDepth
     * @return array
     */
    protected function getNestedRules(string $prefix, int $maxDepth, array $whitelistFilterFields, array $rules = [], int $currentDepth = 1): array
    {
        $rules = array_merge($rules, [
            $prefix.'.*.type' => ['sometimes', 'in:and,or'],
            $prefix.'.*.field' => [
                "required_without:{$prefix}.*.nested",
                'regex:/^[\w.\_\-\>]+$/',
                new WhitelistedField($whitelistFilterFields),
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
                $this->getNestedRules("{$prefix}.*.nested", $maxDepth, $whitelistFilterFields, $rules, ++$currentDepth)
            );
        }

        return $rules;
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
        $depth = $this->nestedFiltersDepth($request->post('aggregate', []), -1);

        Validator::make(
            $request->post(),
            array_merge(
                [
                    'aggregate' => ['sometimes', 'array'],
                    'aggregate.*.relation' => [
                        'required',
                        'regex:/^[\w.\_\-\>]+$/',
                        new WhitelistedField($this->aggregatableBy),
                    ],
                    'aggregate.*.type' => [
                        'required',
                        'in:count,min,max,avg,sum,exists'
                    ],
                    'aggregate.*.filters' => ['sometimes', 'array'],
                ],
                $this->getNestedRules('aggregate.*.filters', $depth, $this->aggregatesFilterableBy)
            )
        )->validate();

        Validator::make(
            $request->query(),
            [
                'aggregateCount' => ['sometimes', 'string', new WhitelistedQueryFields($this->aggregatableBy)],
                'aggregateMin' => ['sometimes', 'string', new WhitelistedQueryFields($this->aggregatableBy)],
                'aggregateMax' => ['sometimes', 'string', new WhitelistedQueryFields($this->aggregatableBy)],
                'aggregateAvg' => ['sometimes', 'string', new WhitelistedQueryFields($this->aggregatableBy)],
                'aggregateSum' => ['sometimes', 'string', new WhitelistedQueryFields($this->aggregatableBy)],
                'aggregateExists' => ['sometimes', 'string', new WhitelistedQueryFields($this->aggregatableBy)],
            ]
        )->validate();
    }

    public function validateIncludes(Request $request): void
    {
        $depth = $this->nestedFiltersDepth($request->post('include', []), -1);

        Validator::make(
            $request->post(),
            array_merge(
                [
                    'include' => ['sometimes', 'array'],
                    'include.*.relation' => [
                        'required',
                        'regex:/^[\w.\_\-\>]+$/',
                        new WhitelistedField($this->includableBy),
                    ],
                    'include.*.filters' => ['sometimes', 'array'],
                ],
                $this->getNestedRules('include.*.filters', $depth, $this->includesFilterableBy)
            )
        )->validate();

        Validator::make(
            $request->query(),
            [
                'include' => ['sometimes', 'string', new WhitelistedQueryFields($this->includableBy)],
            ]
        )->validate();
    }


    // @TODO: implement aggregates in query params to allow access to it from "show" routes for example
    // @TODO: update doc
    // @TODO: update specs
    // @TODO: update tests
}
