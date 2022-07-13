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
     * @inheritDoc
     */
    public function __construct(array $exposedScopes = [], array $filterableBy = [], array $sortableBy = [])
    {
        $this->exposedScopes = $exposedScopes;
        $this->filterableBy = $filterableBy;
        $this->sortableBy = $sortableBy;
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
        $max_depth = ceil(($this->getArrayDepth($request->all()['filters'])) / 2) - 1;

        abort_if($max_depth > config('orion.max_nested_depth'), 422, __('Max nested depth :depth is exceeded', ['depth' => config('orion.max_nested_depth')]));

        Validator::make(
            $request->all(),
            array_merge([
                'filters' => ['sometimes', 'array'],
            ], $this->getNestedRules('filters', $max_depth))
        )->validate();
    }

    protected function getNestedRules($prefix, $max_depth, $rules = [], $current_depth = 1) {
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
            $prefix.'.*.value' => ["required_without:{$prefix}.*.nested", 'nullable'],
            $prefix.'.*.nested' => ['sometimes', 'array'],
        ]);

        if ($max_depth >= $current_depth) {
            $rules = array_merge($rules, $this->getNestedRules("{$prefix}.*.nested", $max_depth, $rules, ++$current_depth));
        }

        return $rules;
    }

    protected function getArrayDepth($array) {
        $max_depth = 0;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->getArrayDepth($value) + 1;

                $max_depth = max($depth, $max_depth);
            }
        }

        return $max_depth;
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
}
