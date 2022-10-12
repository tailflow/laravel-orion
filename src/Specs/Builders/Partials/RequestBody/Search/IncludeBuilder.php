<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\RequestBody\Search;

use Orion\Specs\Builders\Partials\RequestBody\SearchPartialBuilder;

class IncludeBuilder extends SearchPartialBuilder
{
    public function build(): ?array
    {
        if (!count($this->controller->includesFilterableBy())) {
            return null;
        }

        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'relation' => [
                        'type' => 'string',
                        'enum' => $this->controller->includesFilterableBy(),
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
