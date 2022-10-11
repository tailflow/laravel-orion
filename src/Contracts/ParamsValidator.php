<?php

namespace Orion\Contracts;

use Orion\Http\Requests\Request;

interface ParamsValidator
{
    /**
     * ParamsValidator constructor.
     *
     * @param string[] $exposedScopes
     * @param string[] $filterableBy
     * @param string[] $sortableBy
     * @param string[] $aggregatableBy
     * @param string[] $aggregatesFilterableBy
     * @param string[] $includableBy
     * @param string[] $includesFilterableBy
     */
    public function __construct(array $exposedScopes = [], array $filterableBy = [], array $sortableBy = [], array $aggregatableBy = [], array $aggregatesFilterableBy = [], array $includableBy = [], array $includesFilterableBy = []);

    public function validateScopes(Request $request): void;

    public function validateFilters(Request $request): void;

    public function validateSort(Request $request): void;

    public function validateSearch(Request $request): void;

    public function validateAggregators(Request $request): void;

    public function validateIncludes(Request $request): void;
}
