<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Model;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Orion\Specs\Builders\Components\ModelComponentBuilder;
use Orion\ValueObjects\Specs\Component;

use function class_basename;

class ModelResourceComponentBuilder extends ModelComponentBuilder
{
    /**
     * @param Model $resourceModel
     * @return Component
     * @throws Exception
     */
    public function build(Model $resourceModel): Component
    {
        $resourceComponentBaseName = class_basename($resourceModel);

        $component = new Component();
        $component->title = "{$resourceComponentBaseName}Resource";
        $component->type = 'object';
        $component->properties = [
            'allOf' => [
                ['$ref' => "#/components/schemas/{$resourceComponentBaseName}Resource"],
                [
                    'type' => 'object',
                    'properties' => $this->getPropertiesFromSchema($resourceModel)
                ],
            ],
        ];

        return $component;
    }

    /**
     * @param Model $resourceModel
     * @return array
     * @throws Exception
     */
    protected function getPropertiesFromSchema(Model $resourceModel): array
    {
        $columns = $this->schemaManager->getSchemaColumns($resourceModel);

        return collect($columns)
            ->filter(
                function (Column $column) use ($resourceModel) {
                    return !$resourceModel->isFillable($column->getName());
                }
            )->map(
                function (Column $column) use ($resourceModel) {
                    $propertyClass = $this->schemaManager->resolveSchemaPropertyClass($column, $resourceModel);

                    return $this->propertyBuilder->build($column, $propertyClass);
                }
            )->values()
            ->keyBy('name')
            ->toArray();
    }
}
