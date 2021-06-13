<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Requests\Batch;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\ValueObjects\RegisteredResource;
use Orion\ValueObjects\Specs\Request;

class BatchRestoreRequest extends Request
{
    /**
     * @var RegisteredResource
     */
    protected $registeredResource;

    /**
     * BatchDestroyRequest constructor.
     *
     * @param RegisteredResource $registeredResource
     */
    public function __construct(RegisteredResource $registeredResource)
    {
        $this->registeredResource = $registeredResource;
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
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
                                        'type' => $this->registeredResource->getKeyType(),
                                        'description' => 'A list of resource IDs'
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
