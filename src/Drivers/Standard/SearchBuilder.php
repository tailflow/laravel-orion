<?php

declare(strict_types=1);

namespace Orion\Drivers\Standard;

class SearchBuilder implements \Orion\Contracts\SearchBuilder
{
    /**
     * @var string[]
     */
    private array $searchableBy;

    public function __construct(array $searchableBy)
    {
        $this->searchableBy = $searchableBy;
    }

    public function searchableBy(): array
    {
        return $this->searchableBy;
    }
}
