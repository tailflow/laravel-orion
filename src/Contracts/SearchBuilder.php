<?php


namespace Orion\Contracts;

interface SearchBuilder
{
    public function __construct(array $searchableBy);

    public function searchableBy(): array;
}
