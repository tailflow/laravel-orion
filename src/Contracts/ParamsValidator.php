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
     */
    public function __construct(array $exposedScopes = [], array $filterableBy = [], array $sortableBy = []);

    public function validateScopes(Request $request): void;

    public function validateFilters(Request $request): void;

    public function validateSort(Request $request): void;

    public function validateSearch(Request $request): void;
}
