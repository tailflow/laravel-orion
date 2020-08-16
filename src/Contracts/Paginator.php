<?php

namespace Orion\Contracts;

use Orion\Http\Requests\Request;

interface Paginator
{
    public function __construct(int $defaultLimit);

    public function resolvePaginationLimit(Request $request): int;
}
