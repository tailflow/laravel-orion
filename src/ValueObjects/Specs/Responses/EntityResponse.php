<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Responses;

use Orion\ValueObjects\Specs\Response;

class EntityResponse extends Response
{
    public $description = 'OK';
    public $resourceComponentBaseName;

    public function __construct(string $resourceComponentBaseName, int $statusCode = 200)
    {
        $this->resourceComponentBaseName = $resourceComponentBaseName;
        $this->statusCode = $statusCode;
    }

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
                                    '$ref' => "#/components/schemas/{$this->resourceComponentBaseName}Resource",
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}
