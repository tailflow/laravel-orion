<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\Search;

use Orion\Specs\Builders\Partials\SearchPartialBuilder;

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
                        'required' => true
                    ],
                    'operator' => [
                        'type' => 'string',
                        'enum' => ['<','<=','>','>=','=','!=','like','not like','ilike','not ilike','in','not in'],
                        'required' => true
                    ],
                    'value' => [
                        'type' => 'string',
                        'required' => null
                    ]
                ]
            ]
        ];
    }
}
