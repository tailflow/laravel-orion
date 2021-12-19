<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\RequestBody\Search;

use Orion\Specs\Builders\Partials\RequestBody\SearchPartialBuilder;

class SearchBuilder extends SearchPartialBuilder
{
    public function build(): ?array
    {
        if (!count($this->controller->searchableBy())) {
            return null;
        }

        return [
            'type' => 'object',
            'properties' => [
                'value' => [
                    'type' => 'string',
                    'description' => 'A search for the given value will be performed on the following fields: ' . collect(
                            $this->controller->searchableBy()
                        )->join(', ')
                ],
                'case_sensitive' => [
                    'type' => 'boolean',
                    'description' => '(default: true) Set it to false to perform search in case-insensitive way'
                ]
            ]
        ];
    }
}
