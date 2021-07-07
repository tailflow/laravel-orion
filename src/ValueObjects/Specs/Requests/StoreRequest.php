<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Requests;

use Orion\ValueObjects\Specs\Request;

class StoreRequest extends Request
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
                            '$ref' => "#/components/schemas/{$this->resourceComponentBaseName}",
                        ],
                    ],
                ],
            ]
        );
    }
}
