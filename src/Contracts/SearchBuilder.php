<?php


namespace Orion\Contracts;

interface SearchBuilder
{
    public function __construct(array $searchableBy, string $searchStrategy = 'like');

    public function searchableBy(): array;

    public function searchStrategy(): string;
}
