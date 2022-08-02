<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Requests\Batch;

use Orion\ValueObjects\Specs\Request;

class BatchUpdateRequest extends Request
{
    /**
     * @var string
     */
    protected $resourceComponentBaseName;

    public function __construct(string $resourceComponentBaseName)
    {
        $this->resourceComponentBaseName = $resourceComponentBaseName;
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
                                'resources' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        '{key}' => [
                                            '$ref' => "#/components/schemas/{$this->resourceComponentBaseName}",
                                        ],
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
