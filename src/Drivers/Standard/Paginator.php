<?php

namespace Orion\Drivers\Standard;

use Orion\Http\Requests\Request;

class Paginator implements \Orion\Contracts\Paginator
{
    /**
     * @var int $defaultLimit
     */
    protected $defaultLimit;

    /**
     * Paginator constructor.
     *
     * @param int $defaultLimit
     */
    public function __construct(int $defaultLimit)
    {
        $this->defaultLimit = $defaultLimit;
    }

    /**
     * Determine the pagination limit based on the "limit" query parameter or the default, specified by developer.
     *
     * @param Request $request
     * @return int
     */
    public function resolvePaginationLimit(Request $request): int
    {
        $limit = (int) $request->get('limit', $this->defaultLimit);
        return $limit > 0 ? $limit : $this->defaultLimit;
    }
}
