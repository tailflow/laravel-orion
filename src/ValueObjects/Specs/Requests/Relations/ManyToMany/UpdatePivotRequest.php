<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Requests\Relations\ManyToMany;

use Orion\ValueObjects\Specs\Request;

class UpdatePivotRequest extends Request
{
    public function toArray(): array
    {
        return array_merge(
            parent::toArray(),
            [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'pivot' => [
                                    'type' => 'object',
                                    'description' => 'Pivot fields'
                                ]
                            ],
                        ],
                    ],
                ]
            ]
        );
    }
}
