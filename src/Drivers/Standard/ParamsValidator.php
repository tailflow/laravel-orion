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
        Validator::make($request->all(), [
            'scopes' => ['sometimes', 'array'],
            'scopes.*.name' => ['required_with:scopes', 'in:'.implode(',', $this->exposedScopes)],
            'scopes.*.parameters' => ['sometimes', 'array']
        ])->validate();
    }

    public function validateFilters(Request $request): void
    {
        Validator::make($request->all(), [
            'filters' => ['sometimes', 'array'],
            'filters.*.type' => ['sometimes', 'in:and,or'],
            'filters.*.field' => ['required_with:filters', 'regex:/^[\w.]+$/', new WhitelistedField($this->filterableBy)],
            'filters.*.operator' => ['required_with:filters', 'in:<,<=,>,>=,=,!=,like,not like,in,not in'],
            'filters.*.value' => ['present', 'nullable']
        ])->validate();
    }

    public function validateSort(Request $request): void
    {
        Validator::make($request->all(), [
            'sort' => ['sometimes', 'array'],
            'sort.*.field' => ['required_with:sort', 'regex:/^[\w.]+$/', new WhitelistedField($this->sortableBy)],
            'sort.*.direction' => ['sometimes', 'in:asc,desc']
        ])->validate();
    }

    public function validateSearch(Request $request): void
    {
        Validator::make($request->all(), [
            'search' => ['sometimes', 'array'],
            'search.value' => ['string', 'nullable']
        ])->validate();
    }
}
