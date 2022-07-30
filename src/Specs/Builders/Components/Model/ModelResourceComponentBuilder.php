<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Model;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Orion\Http\Resources\Resource;
use Orion\Specs\Builders\Components\ModelComponentBuilder;
use Orion\ValueObjects\Specs\ModelResourceComponent;

class ModelResourceComponentBuilder extends ModelComponentBuilder
{
    /**
     * @param Model $resourceModel
     * @param Resource $resourceResource
     * @return ModelResourceComponent
     * @throws Exception
     */
    public function build(Model $resourceModel, Resource $resourceResource): ModelResourceComponent
    {
        $component = new ModelResourceComponent();
        $component->title = class_basename($resourceResource);
        $component->type = 'object';

        $resourceProperties = $this->getPropertiesFromResource($resourceResource);
        $includedProperties = array_keys($resourceProperties);

        $component->properties = array_merge(
            $resourceProperties,
            $this->getPropertiesFromSchema($resourceModel, $includedProperties)
        );

        return $component;
    }

    /**
     * @param Resource $resourceResource
     * @return array
     * @throws Exception
     */
    protected function getPropertiesFromResource(Resource $resourceResource): array
    {
        $properties = $this->resourceManager->getResourceProperties($resourceResource);

        return collect($properties)
            ->filter(function ($value, $property) {
                return is_string($property);
            })
            ->map(function ($value, $property) {
                $propertyClass = $this->resourceManager->resolveResourcePropertyClass($property, $value);

                return $this->propertyBuilder->buildFromResource($property, true, $propertyClass);
            })
            ->values()
            ->keyBy('name')
            ->toArray();
    }

    /**
     * @param Model $resourceModel
     * @return array
     * @throws Exception
     */
    protected function getPropertiesFromSchema(Model $resourceModel, array $includedProperties): array
    {
        $columns = $this->schemaManager->getSchemaColumns($resourceModel);

        return collect($columns)
            ->filter(function (Column $column) use ($includedProperties) {
                return in_array($column->getName(), $includedProperties, true);
            })
            ->map(function (Column $column) use ($resourceModel) {
                $propertyClass = $this->schemaManager->resolveSchemaPropertyClass($column, $resourceModel);

                return $this->propertyBuilder->build($column, $propertyClass);
            })
            ->values()
            ->keyBy('name')
            ->toArray();
    }
}
