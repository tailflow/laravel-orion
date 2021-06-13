<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Responses\Success\Relation\ManyToMany;

use Illuminate\Database\Eloquent\Model;
use Orion\ValueObjects\Specs\Response;

class SyncResponse extends Response
{
    /** @var Model */
    protected $resourceModel;

    /**
     * AttachResponse constructor.
     *
     * @param Model $resourceModel
     */
    public function __construct(Model $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    public function toArray(): array
    {
        $itemsType = $this->resourceModel->getKeyType() === 'int' ? 'integer' : 'string';

        return array_merge(
            parent::toArray(),
            [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'attached' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => $itemsType,
                                    ],
                                ],
                                'detached' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => $itemsType,
                                    ],
                                ],
                                'updated' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => $itemsType,
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
