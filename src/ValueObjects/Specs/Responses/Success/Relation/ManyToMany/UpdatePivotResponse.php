<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Responses\Success\Relation\ManyToMany;

use Illuminate\Database\Eloquent\Model;
use Orion\ValueObjects\Specs\Response;

class UpdatePivotResponse extends Response
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
        return array_merge(
            parent::toArray(),
            [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'updated' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => $this->resourceModel->getKeyType() === 'int' ? 'integer' : 'string',
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
