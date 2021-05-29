<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\Search;

use Orion\Specs\Builders\Partials\SearchPartialBuilder;

class ScopesBuilder extends SearchPartialBuilder
{
    public function build(): ?array
    {
        if (!count($this->controller->exposedScopes())) {
            return null;
        }

        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'enum' => $this->controller->exposedScopes(),
                        'required' => true
                    ],
                    'parameters' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string'
                        ]
                    ]
                ]
            ]
        ];
    }
}
