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

        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
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
                        'enum' => ['<','<=','>','>=','=','!=','like','not like','ilike','not ilike','in','not in'],
                    ],
                    'value' => [
                        'type' => 'string',
                    ]
                ],
                'required' => [
                    'field', 'value'
                ]
            ]
        ];
    }
}
