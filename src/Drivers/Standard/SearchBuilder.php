<?php

namespace Orion\Drivers\Standard;

use InvalidArgumentException;

class SearchBuilder implements \Orion\Contracts\SearchBuilder
{
    /**
     * @var array $allowedStrategies
     */
    private static $allowedStrategies = ['like', 'fulltext'];

    /**
     * @var array
     */
    private $searchableBy;

    /**
     * @var string
     */
    private $strategy;

    public function __construct(array $searchableBy, string $strategy = 'like')
    {
        $this->validateStrategy($strategy);

        $this->searchableBy = $searchableBy;
        $this->strategy = $strategy;
    }

    public function searchableBy(): array
    {
        return $this->searchableBy;
    }

    public function searchStrategy(): string
    {
        return $this->strategy;
    }

    private function validateStrategy(string $strategy): void
    {
        if (!in_array($strategy, self::$allowedStrategies, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported search strategy [%s]. Allowed values: %s.',
                    $strategy,
                    implode(', ', self::$allowedStrategies)
                )
            );
        }
    }
}
