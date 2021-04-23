<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Responses;

use Orion\ValueObjects\Specs\Response;

class PaginatedCollectionResponse extends Response
{
    public $statusCode = 200;
    public $description = 'OK';
    public $resourceComponentBaseName;

    public function __construct(string $resourceComponentBaseName)
    {
        $this->resourceComponentBaseName = $resourceComponentBaseName;
    }

    // TODO: move links and meta definitions to components
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
                                'data' => [
                                    'type' => 'array',
                                    'items' => [
                                        '$ref' => "#/components/schemas/{$this->resourceComponentBaseName}Resource",
                                    ],
                                ],
                                'links' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'first' => [
                                            'type' => 'string',
                                            'format' => 'uri',
                                        ],
                                        'last' => [
                                            'type' => 'string',
                                            'format' => 'uri',
                                        ],
                                        'prev' => [
                                            'type' => 'string',
                                            'format' => 'uri',
                                        ],
                                        'next' => [
                                            'type' => 'string',
                                            'format' => 'uri',
                                        ],
                                    ],
                                ],
                                'meta' => [
                                    'current_page' => [
                                        'type' => 'integer',
                                    ],
                                    'from' => [
                                        'type' => 'integer',
                                    ],
                                    'last_page' => [
                                        'type' => 'integer',
                                    ],
                                    'path' => [
                                        'type' => 'string',
                                    ],
                                    'per_page' => [
                                        'type' => 'integer',
                                    ],
                                    'to' => [
                                        'type' => 'integer',
                                    ],
                                    'total' => [
                                        'type' => 'integer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}
