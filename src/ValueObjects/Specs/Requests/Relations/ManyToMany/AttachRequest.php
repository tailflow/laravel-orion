<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Requests\Relations\ManyToMany;

use Orion\ValueObjects\Specs\Request;

class AttachRequest extends Request
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
                                'resources' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'description' => 'A key-value pairs, where keys are relation resource IDs and values are objects representing pivot fields'
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
            ]
        );
    }
}
