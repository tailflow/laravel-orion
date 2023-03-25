<?php

namespace Orion\Drivers\Standard;

use Orion\Exceptions\MaxPaginationLimitExceededException;
use Orion\Http\Requests\Request;

class Paginator implements \Orion\Contracts\Paginator
{
    /**
     * @var int $defaultLimit
     */
    protected $defaultLimit;

    /**
     * @var int|null $maxLimit
     */
    protected $maxLimit;

    /**
     * Paginator constructor.
     *
     * @param int $defaultLimit
     * @param int|null $maxLimit
     */
    public function __construct(int $defaultLimit, ?int $maxLimit)
    {
        $this->defaultLimit = $defaultLimit;
        $this->maxLimit = $maxLimit;
    }

    /**
     * Determine the pagination limit based on the "limit" query parameter or the default, specified by developer.
     *
     * @param Request $request
     * @return int
     */
    public function resolvePaginationLimit(Request $request): int
    {
        $limit = (int) $request->get('limit');

        return tap($limit > 0 ? $limit : $this->defaultLimit, function ($limit) {
            if ($this->maxLimit && $limit > $this->maxLimit) {
                throw new MaxPaginationLimitExceededException(422, __("Pagination limit of :max is exceeded. Current: :limit", ['max' => $this->maxLimit, 'limit' => $limit]));
            }
        });
    }
}
