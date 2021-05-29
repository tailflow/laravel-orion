<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Partials\Search;

use Orion\Specs\Builders\Partials\SearchPartialBuilder;

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
                ]
            ]
        ];
    }
}
