<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Requests;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\SchemaPropertyBuilder;
use Orion\ValueObjects\Specs\Request;

class CustomRequest extends Request
{
    /**
     * @var SchemaPropertyBuilder
     */
    protected $schemaPropertyBuilder;
    /**
     * @var string
     */
    protected $requestClass;

    public function __construct(SchemaPropertyBuilder $schemaPropertyBuilder, string $requestClass)
    {
        $this->schemaPropertyBuilder = $schemaPropertyBuilder;
        $this->requestClass = $requestClass;
    }

    /**
     * @throws BindingResolutionException
     */
    public function toArray(): array
    {
        /** @var \Orion\Http\Requests\Request $requestClassInstance */
        $requestClassInstance = app()->make($this->requestClass);

        return array_merge(
            parent::toArray(),
            [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => $this->schemaPropertyBuilder->build(
                                $requestClassInstance->getSchema()
                            ),
                        ],
                    ],
                ],
            ]
        );
    }
}
