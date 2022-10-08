<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Responses\Success;

use Illuminate\Http\Resources\Json\JsonResource;
use Orion\ValueObjects\Specs\Response;

class EntityResponse extends Response
{
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
                        'schema' => JsonResource::$wrap ?
                            [
                                'type'       => 'object',
                                'properties' => [
                                    JsonResource::$wrap => [
                                        '$ref' => "#/components/schemas/{$this->resourceComponentBaseName}Resource",
                                    ],
                                ],
                            ] :
                            [
                                '$ref' => "#/components/schemas/{$this->resourceComponentBaseName}Resource",
                            ],
                    ],
                ],
            ]
        );
    }
}
