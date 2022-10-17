<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\RequestBody\Search;

use Orion\Specs\Builders\Partials\RequestBody\SearchPartialBuilder;

class AggregatesBuilder extends SearchPartialBuilder
{
    public function build(): ?array
    {
        if (!count($this->controller->aggregates())) {
            return null;
        }

        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'type' => [
                        'type' => 'string',
                        'enum' => ['count', 'min', 'max', 'avg', 'sum', 'exists'],
                    ],
                    'relation' => [
                        'type' => 'string',
                        'enum' => $this->controller->aggregates(),
                    ],
                    'filters' => [
                        'type' => 'object',
                        'properties' => app()->makeWith(FiltersBuilder::class, ['controller' => get_class($this->controller)])->build(),
                    ],
                ]
            ]
        ];
    }
}
