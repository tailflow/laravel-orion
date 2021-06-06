<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\RequestBody\Search;

use Orion\Specs\Builders\Partials\RequestBody\SearchPartialBuilder;

class SortBuilder extends SearchPartialBuilder
{
    public function build(): ?array
    {
        if (!count($this->controller->sortableBy())) {
            return null;
        }

        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'field' => [
                        'type' => 'string',
                        'enum' => $this->controller->sortableBy(),
                    ],
                    'direction' => [
                        'type' => 'string',
                        'enum' => ['asc', 'desc']
                    ]
                ],
                'required' => [
                    'field'
                ]
            ]
        ];
    }
}
