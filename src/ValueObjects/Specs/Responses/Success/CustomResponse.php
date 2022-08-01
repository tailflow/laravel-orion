<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Responses\Success;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\SchemaPropertyBuilder;
use Orion\ValueObjects\Specs\Response;

class CustomResponse extends Response
{
    /**
     * @var SchemaPropertyBuilder
     */
    protected $schemaPropertyBuilder;
    /**
     * @var string
     */
    protected $responseClass;

    public function __construct(SchemaPropertyBuilder $schemaPropertyBuilder, string $responseClass)
    {
        $this->schemaPropertyBuilder = $schemaPropertyBuilder;
        $this->responseClass = $responseClass;
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    public function toArray(): array
    {
        /** @var \Orion\Http\Responses\Response $responseClassInstance */
        $responseClassInstance = app()->make($this->responseClass);

        return array_merge(
            parent::toArray(),
            [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => $this->schemaPropertyBuilder->build(
                                $responseClassInstance->getSchema()
                            ),
                        ],
                    ],
                ],
            ]
        );
    }
}
