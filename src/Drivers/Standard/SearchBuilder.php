<?php

namespace Orion\Drivers\Standard;

class SearchBuilder implements \Orion\Contracts\SearchBuilder
{
    /**
     * @var array
     */
    private $searchableBy;

    public function __construct(array $searchableBy)
    {
        $this->searchableBy = $searchableBy;
    }

    public function searchableBy(): array
    {
        return $this->searchableBy;
    }
}
