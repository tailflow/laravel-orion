<?php

declare(strict_types=1);

namespace Orion\Contracts;

interface SearchBuilder
{
    public function __construct(array $searchableBy);

    public function searchableBy(): array;
}
