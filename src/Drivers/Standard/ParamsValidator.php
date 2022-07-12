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
        Validator::make(
            $request->all(),
            [
                'filters' => ['sometimes', 'array']
            ]
        )->validate();

        $this->validateNestedFilter($request->all()['filters']);
    }

    protected function validateNestedFilter($nested) {
        foreach ($nested as $filter) {
            Validator::make(
                $filter,
                [
                    'type' => ['sometimes', 'in:and,or'],
                    'field' => [
                        'required_without:nested',
                        'regex:/^[\w.\_\-\>]+$/',
                        new WhitelistedField($this->filterableBy),
                    ],
                    'operator' => [
                        'sometimes',
                        'in:<,<=,>,>=,=,!=,like,not like,ilike,not ilike,in,not in,all in,any in',
                    ],
                    'value' => ['required_without:nested', 'nullable'],
                    'nested' => ['sometimes', 'array'],
                ]
            )->validate();

            $this->validateNestedFilter($filter['nested'] ?? []);
        }
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
