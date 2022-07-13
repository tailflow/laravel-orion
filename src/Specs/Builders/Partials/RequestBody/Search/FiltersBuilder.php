<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\RequestBody\Search;

use Orion\Specs\Builders\Partials\RequestBody\SearchPartialBuilder;

class FiltersBuilder extends SearchPartialBuilder
{
    public function build(): ?array
    {
        if (!count($this->controller->filterableBy())) {
            return null;
        }

        $filters = [
            'type' => [
                'type' => 'string',
                'enum' => ['and', 'or'],
            ],
            'field' => [
                'type' => 'string',
                'enum' => $this->controller->filterableBy(),
            ],
            'operator' => [
                'type' => 'string',
                'enum' => ['<','<=','>','>=','=','!=','like','not like','ilike','not ilike','in','not in', 'all in', 'any in'],
            ],
            'value' => [
                'type' => 'string',
            ]
        ];

        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => array_merge($filters, [
                    'nested' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => $filters
                        ]
                    ]
                ])
            ]
        ];
    }
}
