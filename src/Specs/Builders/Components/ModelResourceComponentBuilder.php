<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components;

use Illuminate\Database\Eloquent\Model;
use Orion\ValueObjects\Specs\Component;

class ModelResourceComponentBuilder
{
    /**
     * @param Model $resourceModel
     * @return Component
     */
    public function build(Model $resourceModel): Component
    {
        $resourceComponentBaseName = class_basename($resourceModel);
        $timestampsComponent = method_exists($resourceModel, 'trashed') ? 'SoftDeletableResourceTimestampsComponent' : 'ResourceTimestampsComponent';

        $component = new Component();
        $component->title = "{$resourceComponentBaseName}Resource";
        $component->type = 'object';
        $component->properties = [
            'allOf' => [
                ['$ref' => "#/components/schemas/{$resourceComponentBaseName}Resource"],
                ['$ref' => "#/components/schemas/{$timestampsComponent}"],
                [
                    'type' => 'object',
                    'properties' => [
                        $resourceModel->getKeyName() => [
                            'type' => $resourceModel->getKeyType() === 'int' ? 'integer' : 'string',
                        ],
                    ],
                ],
            ],
        ];

        return $component;
    }
}
